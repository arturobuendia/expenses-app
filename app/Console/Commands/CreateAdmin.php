<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class CreateAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-admin
                            {--email= : Email del administrador}
                            {--name= : Nombre del administrador}
                            {--password= : Contraseña (omitir para escribirla de forma oculta)}
                            {--update : Actualizar la contraseña si el administrador ya existe}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create the admin account for this installation';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->option('email') ?: text(
            label: 'Email del administrador',
            placeholder: 'admin@example.com',
            required: 'El email es obligatorio.',
            validate: fn (string $value) => filter_var($value, FILTER_VALIDATE_EMAIL)
                ? null
                : 'El email no es válido.',
        );

        $name = $this->option('name') ?: text(
            label: 'Nombre del administrador',
            placeholder: 'Admin',
            required: 'El nombre es obligatorio.',
        );

        $password = $this->option('password');

        if ($password === null) {
            $password = password(
                label: 'Contraseña del administrador',
                placeholder: '••••••••',
                required: 'La contraseña es obligatoria.',
                validate: fn (string $value) => $this->validatePassword($value),
            );
        } elseif ($error = $this->validatePassword($password)) {
            $this->error($error);

            return self::FAILURE;
        }

        $existing = User::where('email', $email)->first();

        if ($existing && ! $this->option('update')) {
            $this->warn("Ya existe un administrador con el email {$email}. Usa --update para cambiarlo.");

            return self::SUCCESS;
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => $password],
        );

        $this->info($existing
            ? "Administrador actualizado: {$user->email}"
            : "Administrador creado: {$user->email}");

        return self::SUCCESS;
    }

    /**
     * Validate the password against the app's password policy.
     *
     * Reuses Password::defaults() so the command and the web app enforce the
     * exact same rules (min 8 locally, the full strict rule in production).
     * Returns an error message, or null when it is valid.
     */
    private function validatePassword(string $value): ?string
    {
        if ($value === '') {
            return 'La contraseña es obligatoria.';
        }

        $rules = Password::defaults();

        $validator = Validator::make(
            ['password' => $value],
            ['password' => ['required', $rules]],
            [
                'password.min' => 'La contraseña debe tener al menos '.$rules->appliedRules()['min'].' caracteres.',
                'password.mixed' => 'La contraseña debe combinar mayúsculas y minúsculas.',
                'password.letters' => 'La contraseña debe contener al menos una letra.',
                'password.numbers' => 'La contraseña debe contener al menos un número.',
                'password.symbols' => 'La contraseña debe contener al menos un símbolo.',
                'password.uncompromised' => 'La contraseña aparece en una filtración conocida; elige otra.',
            ],
        );

        return $validator->fails()
            ? $validator->errors()->first('password')
            : null;
    }
}
