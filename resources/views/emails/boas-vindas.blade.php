<x-mail::message>
# Bem-vindo, {{ $user->name }}!

Sua conta foi criada com sucesso no sistema de câmeras de segurança.

Agora você pode acessar o sistema e, após ter uma assinatura ativa, visualizar as câmeras liberadas para o seu perfil.

**Seus dados de acesso:**

- **E-mail:** {{ $user->email }}
- **URL do sistema:** [{{ config('app.url') }}]({{ config('app.url') }})

<x-mail::button :url="config('app.url')">
Acessar o sistema
</x-mail::button>

Se você não criou esta conta, por favor ignore este e-mail.

Atenciosamente,<br>
Equipe {{ config('app.name') }}
</x-mail::message>
