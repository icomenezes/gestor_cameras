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

        $script = base_path('novo-cliente.sh');
        $output = null;

        if (file_exists($script)) {
            $cmd = sprintf(
                'bash %s --slug %s --domain %s --email %s --password %s 2>&1',
                escapeshellarg($script),
                escapeshellarg($slug),
                escapeshellarg($domain),
                escapeshellarg($data['email']),
                escapeshellarg($data['password'])
            );
            $output = shell_exec($cmd);
            $tenant->update(['meta' => ['provision_log' => $output]]);
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
