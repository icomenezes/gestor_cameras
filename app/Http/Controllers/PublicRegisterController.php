<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PublicRegisterController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'name'     => ['required', 'string', 'max:150'],
                'email'    => ['required', 'email', 'max:255'],
                'password' => ['required', 'string', 'min:8'],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => collect($e->errors())->flatten()->first(),
            ], 422);
        }

        $slug = $this->uniqueSlug($data['name']);
        $domain = $slug . '.camerasonline.net.br';

        if (Tenant::where('admin_email', $data['email'])->exists()) {
            return response()->json([
                'error' => 'Já existe uma conta com esse e-mail.',
            ], 409);
        }

        $tenant = Tenant::create([
            'slug'             => $slug,
            'name'             => $data['name'],
            'domain'           => $domain,
            'admin_email'      => $data['email'],
            'docker_container' => 'cameras_' . $slug . '_app',
            'status'           => 'active',
        ]);

        // Chama o agente de provisionamento rodando no host (provision-agent.php)
        // O agente executa novo-cliente.sh fora do container Docker
        $agentUrl = 'http://172.18.0.1:9099'; // gateway da rede cameras_net → host
        $secret   = env('PROVISION_SECRET', 'trocar-por-segredo-forte');

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(30)->post($agentUrl, [
                'secret'   => $secret,
                'slug'     => $slug,
                'domain'   => $domain,
                'email'    => $data['email'],
                'password' => $data['password'],
            ]);

            $tenant->update(['meta' => ['provision_status' => $response->status() === 202 ? 'started' : 'agent_error_' . $response->status()]]);
        } catch (\Throwable $e) {
            // Broken pipe ou timeout — o agente recebeu e está processando em background
            $tenant->update(['meta' => ['provision_status' => 'dispatched', 'provision_note' => $e->getMessage()]]);
        }

        return response()->json([
            'slug'  => $slug,
            'url'   => 'https://' . $domain,
            'login' => $data['email'],
        ], 201);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name, '-');
        $base = preg_replace('/[^a-z0-9\-]/', '', $base) ?: 'academia';
        $slug = $base;
        $i    = 2;

        while (Tenant::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }
}
