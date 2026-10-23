<?php

use App\Models\Admin\Admin;
use App\Models\Admin\Passkey;
use Spatie\LaravelPasskeys\Actions\ConfigureCeremonyStepManagerFactoryAction;
use Spatie\LaravelPasskeys\Actions\FindPasskeyToAuthenticateAction;
use Spatie\LaravelPasskeys\Actions\GeneratePasskeyAuthenticationOptionsAction;
use Spatie\LaravelPasskeys\Actions\GeneratePasskeyRegisterOptionsAction;
use Spatie\LaravelPasskeys\Actions\StorePasskeyAction;

/*
| Passkeys are the optional second step of the admin sign-in. They are
| bound to the admin hostname (the "relying party").
*/

return [
    'redirect_to_after_login' => '/',

    'actions' => [
        'generate_passkey_register_options' => GeneratePasskeyRegisterOptionsAction::class,
        'store_passkey' => StorePasskeyAction::class,
        'generate_passkey_authentication_options' => GeneratePasskeyAuthenticationOptionsAction::class,
        'find_passkey' => FindPasskeyToAuthenticateAction::class,
        'configure_ceremony_step_manager_factory' => ConfigureCeremonyStepManagerFactoryAction::class,
    ],

    'relying_party' => [
        'name' => 'OpenRevise admin',
        'id' => env('OPENREVISE_ADMIN_DOMAIN', 'admin.'.env('OPENREVISE_DOMAIN', 'openrevise.com')),
        'icon' => null,
    ],

    'models' => [
        'passkey' => Passkey::class,
        'authenticatable' => Admin::class,
    ],
];
