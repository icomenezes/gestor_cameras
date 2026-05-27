<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Process;

class TenantController extends Controller
{
    public function index()
    {
        $tenants = Tenant::orderBy('name')->get();
        return view('superadmin.tenants.index', compact('tenants'));
    }

    public function create()
    {
        return view('superadmin.tenants.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'slug'        => ['required', 'string', 'max:50', 'unique:tenants', 'regex:/^[a-z0-9\-]+$/'],
            'name'        => ['required', 'string', 'max:150'],
            'domain'      => ['required', 'string', 'max:255', 'unique:tenants'],
            'admin_email' => ['required', 'email'],
            'password'    => ['required', 'string', 'min:8'],
        ]);

        $tenant = Tenant::create([
            'slug'             => $request->slug,
            'name'             => $request->name,
            'domain'           => $request->domain,
            'admin_email'      => $request->admin_email,
            'docker_container' => 'cameras_' . $request->slug . '_app',
            'status'           => 'active',
        ]);

        // Executa o script de provisionamento no servidor (via SSH ou shell)
        $script = base_path('novo-cliente.sh');
        if (file_exists($script)) {
            $cmd = sprintf(
                'bash %s --slug %s --domain %s --email %s --password %s 2>&1',
                escapeshellarg($script),
                escapeshellarg($request->slug),
                escapeshellarg($request->domain),
                escapeshellarg($request->admin_email),
                escapeshellarg($request->password)
            );
            $output = shell_exec($cmd);
            $tenant->update(['meta' => array_merge($tenant->meta ?? [], ['provision_log' => $output])]);
        }

        return redirect()->route('superadmin.tenants.index')
            ->with('success', "Tenant \"{$tenant->name}\" criado.");
    }

    public function suspend(Tenant $tenant)
    {
        $tenant->update(['status' => 'suspended']);

        $container = $tenant->containerName();
        shell_exec("docker stop {$container} 2>&1");

        return back()->with('success', "Tenant \"{$tenant->name}\" suspenso.");
    }

    public function activate(Tenant $tenant)
    {
        $tenant->update(['status' => 'active']);

        $container = $tenant->containerName();
        shell_exec("docker start {$container} 2>&1");

        return back()->with('success', "Tenant \"{$tenant->name}\" reativado.");
    }

    public function sync(Tenant $tenant)
    {
        $container = $tenant->containerName();

        // Busca contagem de câmeras e usuários via docker exec + artisan tinker
        $cameras = (int) shell_exec("docker exec {$container} php /var/www/html/artisan tinker --execute=\"echo App\\\\Models\\\\Camera::count();\" 2>/dev/null");
        $users   = (int) shell_exec("docker exec {$container} php /var/www/html/artisan tinker --execute=\"echo App\\\\Models\\\\User::where('role','client')->count();\" 2>/dev/null");
        $subs    = (int) shell_exec("docker exec {$container} php /var/www/html/artisan tinker --execute=\"echo App\\\\Models\\\\Subscription::where('status','active')->count();\" 2>/dev/null");

        $tenant->update([
            'meta'           => array_merge($tenant->meta ?? [], compact('cameras', 'users', 'subs')),
            'last_synced_at' => now(),
        ]);

        return back()->with('success', 'Dados sincronizados.');
    }
}
