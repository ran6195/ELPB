<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Company;
use App\Utils\JWTHandler;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthController
{
    /**
     * Login
     */
    public function login(Request $request, Response $response)
    {
        $data = json_decode($request->getBody()->getContents(), true);

        if (empty($data['email']) || empty($data['password'])) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Email and password are required'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $user = User::where('email', $data['email'])->first();

        if (!$user || !password_verify($data['password'], $user->password)) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Invalid credentials'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        // Genera JWT token
        $token = JWTHandler::encode([
            'id' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
            'company_id' => $user->company_id
        ]);

        // Carica la relazione company se esiste
        if ($user->company_id) {
            $user->load('company');
        }

        $response->getBody()->write(json_encode([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'company_id' => $user->company_id,
                'company' => $user->company
            ]
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Register (solo per admin - creazione nuovi utenti/aziende)
     */
    public function register(Request $request, Response $response)
    {
        $data = json_decode($request->getBody()->getContents(), true);

        // Validazione
        if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Name, email and password are required'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Verifica se l'email esiste già
        if (User::where('email', $data['email'])->exists()) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Email already exists'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Se viene creata una company, crea prima l'azienda
        $companyId = null;
        if (!empty($data['company_name'])) {
            $company = Company::create([
                'name' => $data['company_name'],
                'email' => $data['email']
            ]);
            $companyId = $company->id;
        } elseif (!empty($data['company_id'])) {
            $companyId = $data['company_id'];
        }

        // Crea l'utente
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'role' => $data['role'] ?? 'user',
            'company_id' => $companyId
        ]);

        $user->load('company');

        $response->getBody()->write(json_encode([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'company_id' => $user->company_id,
                'company' => $user->company
            ]
        ]));

        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    }

    /**
     * Get current user data
     */
    public function me(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');

        if (!$user) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Unauthorized'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        // Reload user con company
        $user = User::with('company')->find($user->id);

        $response->getBody()->write(json_encode([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'company_id' => $user->company_id,
                'company' => $user->company
            ]
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Get all companies (solo admin)
     */
    public function getCompanies(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');

        if (!$user || !$user->isAdmin()) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Unauthorized'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        $companies = Company::with('users')->get();

        $response->getBody()->write(json_encode($companies));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Get all users (admin o company manager)
     */
    public function getUsers(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');

        if (!$user) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Unauthorized'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        if ($user->isAdmin()) {
            // Admin vede tutti gli utenti
            $users = User::with('company')->get();
        } elseif ($user->isCompany()) {
            // Company manager vede solo gli utenti della sua azienda
            $users = User::where('company_id', $user->company_id)->with('company')->get();
        } else {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Unauthorized'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        $response->getBody()->write(json_encode($users));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Create company (solo admin)
     * Crea automaticamente anche l'utente company manager
     */
    public function createCompany(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');

        if (!$user || !$user->isAdmin()) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Unauthorized'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        $data = json_decode($request->getBody()->getContents(), true);

        // Validazione dati società
        if (empty($data['name']) || empty($data['email'])) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Company name and email are required'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Validazione dati utente manager
        if (empty($data['manager_name']) || empty($data['manager_email']) || empty($data['manager_password'])) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Manager name, email and password are required'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Verifica se l'email della società esiste già
        if (Company::where('email', $data['email'])->exists()) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Company email already exists'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Verifica se l'email del manager esiste già
        if (User::where('email', $data['manager_email'])->exists()) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Manager email already exists'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Crea la società
        $company = Company::create([
            'name' => $data['name'],
            'email' => $data['email']
        ]);

        // Crea l'utente company manager
        $manager = User::create([
            'name' => $data['manager_name'],
            'email' => $data['manager_email'],
            'password' => password_hash($data['manager_password'], PASSWORD_DEFAULT),
            'role' => 'company',
            'company_id' => $company->id
        ]);

        $manager->load('company');

        $response->getBody()->write(json_encode([
            'success' => true,
            'company' => $company,
            'manager' => [
                'id' => $manager->id,
                'name' => $manager->name,
                'email' => $manager->email,
                'role' => $manager->role,
                'company_id' => $manager->company_id,
                'company' => $manager->company
            ]
        ]));

        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    }

    /**
     * Update user (solo admin)
     */
    public function updateUser(Request $request, Response $response, array $args)
    {
        $currentUser = $request->getAttribute('user');

        if (!$currentUser || !$currentUser->isAdmin()) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Unauthorized'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        $userId = $args['id'];
        $user = User::find($userId);

        if (!$user) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'User not found'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $data = json_decode($request->getBody()->getContents(), true);

        // Aggiorna solo i campi forniti
        if (isset($data['name'])) {
            $user->name = $data['name'];
        }
        if (isset($data['email'])) {
            // Verifica unicità email
            if ($data['email'] !== $user->email && User::where('email', $data['email'])->exists()) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Email already exists'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            $user->email = $data['email'];
        }
        if (isset($data['role'])) {
            $user->role = $data['role'];
        }
        if (array_key_exists('company_id', $data)) {
            $user->company_id = $data['company_id'];
        }
        if (isset($data['password']) && !empty($data['password'])) {
            $user->password = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        $user->save();
        $user->load('company');

        $response->getBody()->write(json_encode([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'company_id' => $user->company_id,
                'company' => $user->company
            ]
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Delete user (solo admin)
     */
    public function deleteUser(Request $request, Response $response, array $args)
    {
        $currentUser = $request->getAttribute('user');

        if (!$currentUser || !$currentUser->isAdmin()) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Unauthorized'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        $userId = $args['id'];

        // Non permettere di eliminare se stesso
        if ($currentUser->id == $userId) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Cannot delete yourself'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $user = User::find($userId);

        if (!$user) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'User not found'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $user->delete();

        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'User deleted successfully'
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Delete company (solo admin)
     */
    public function deleteCompany(Request $request, Response $response, array $args)
    {
        $currentUser = $request->getAttribute('user');

        if (!$currentUser || !$currentUser->isAdmin()) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Unauthorized'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        $companyId = $args['id'];
        $company = Company::find($companyId);

        if (!$company) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Company not found'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        // Verifica se ci sono utenti associati
        $usersCount = User::where('company_id', $companyId)->count();
        if ($usersCount > 0) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => "Cannot delete company with {$usersCount} associated users"
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $company->delete();

        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'Company deleted successfully'
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Create user in company (company manager)
     */
    public function createUserInCompany(Request $request, Response $response)
    {
        $currentUser = $request->getAttribute('user');

        if (!$currentUser || !$currentUser->isCompany()) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Unauthorized'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        $data = json_decode($request->getBody()->getContents(), true);

        // Validazione
        if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Name, email and password are required'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Verifica se l'email esiste già
        if (User::where('email', $data['email'])->exists()) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Email already exists'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Crea l'utente nella company del manager
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'role' => 'user',
            'company_id' => $currentUser->company_id
        ]);

        $user->load('company');

        $response->getBody()->write(json_encode([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'company_id' => $user->company_id,
                'company' => $user->company
            ]
        ]));

        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    }

    /**
     * Delete user from company (company manager)
     */
    public function deleteUserFromCompany(Request $request, Response $response, array $args)
    {
        $currentUser = $request->getAttribute('user');

        if (!$currentUser || !$currentUser->isCompany()) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Unauthorized'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        $userId = $args['id'];
        $user = User::find($userId);

        if (!$user) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'User not found'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        // Verifica che l'utente appartenga alla company del manager
        if ($user->company_id !== $currentUser->company_id) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Unauthorized - User does not belong to your company'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        // Non può eliminare se stesso
        if ($user->id === $currentUser->id) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Cannot delete yourself'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Verifica che non abbia pagine associate
        $pagesCount = \App\Models\Page::where('user_id', $userId)->count();
        if ($pagesCount > 0) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => "Cannot delete user with {$pagesCount} associated pages"
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $user->delete();

        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'User deleted successfully'
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Get users with pages count (company manager)
     */
    public function getUsersWithPagesCount(Request $request, Response $response)
    {
        $currentUser = $request->getAttribute('user');

        if (!$currentUser || !$currentUser->isCompany()) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Unauthorized'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        // Ottieni tutti gli utenti della company con il conteggio delle pagine
        $users = User::where('company_id', $currentUser->company_id)
            ->with('company')
            ->withCount('pages')
            ->get();

        $response->getBody()->write(json_encode($users));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
