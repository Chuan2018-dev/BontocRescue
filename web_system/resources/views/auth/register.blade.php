@php
    $html = file_get_contents(base_path('../02_auth/register_screen/code.html'));
    $shortBrand = 'Bontoc Rescue';
    $systemName = 'AI-Powered LoRa Emergency Response and Severity Detection System with Intelligent Location Monitoring';
    $selectedRole = old('role', 'civilian');
    $pwaHead = view('partials.pwa-head')->render();
    $pwaScript = '<script src="'.asset('pwa-helper.js').'"></script>';
    $authHelperScript = '<script src="'.asset('auth-password.js').'"></script>';

    $message = '';
    if ($errors->any()) {
        $message = '<div class="mb-6 rounded-lg bg-error-container px-4 py-3 text-sm font-semibold text-on-error-container">'
            . e($errors->first())
            . '</div>';
    }

    $roleField = '<div class="space-y-3">'
        . '<div class="flex items-center justify-between gap-3">'
        . '<label class="text-[10px] font-label font-bold uppercase tracking-[0.05em] text-outline">Account Role</label>'
        . '<span class="text-[9px] text-surface-dim font-label tracking-wide">Choose reporting or coordination access</span>'
        . '</div>'
        . '<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">'
        . '<label class="block cursor-pointer">'
        . '<input class="peer sr-only" name="role" value="civilian" type="radio" required '.($selectedRole === 'civilian' ? 'checked' : '').'/>'
        . '<span class="flex min-h-[126px] flex-col justify-between rounded-xl border border-surface-container-highest bg-surface-container-low px-4 py-4 transition-all duration-200 hover:border-secondary peer-checked:border-primary peer-checked:bg-primary/5 peer-checked:shadow-lg peer-checked:shadow-primary/10">'
        . '<span class="flex items-center justify-between gap-3">'
        . '<span class="material-symbols-outlined text-secondary">person</span>'
        . '<span class="text-[10px] font-bold uppercase tracking-[0.12em] text-outline">Field Reporter</span>'
        . '</span>'
        . '<span class="mt-4 block text-sm font-semibold text-on-surface">User / Civilian</span>'
        . '<span class="mt-2 block text-xs leading-relaxed text-on-surface-variant">Capture evidence, GPS, and AI severity results for fast emergency reporting.</span>'
        . '</span>'
        . '</label>'
        . '<label class="block cursor-pointer">'
        . '<input class="peer sr-only" name="role" value="responder" type="radio" required '.($selectedRole === 'responder' ? 'checked' : '').'/>'
        . '<span class="flex min-h-[126px] flex-col justify-between rounded-xl border border-surface-container-highest bg-surface-container-low px-4 py-4 transition-all duration-200 hover:border-secondary peer-checked:border-primary peer-checked:bg-primary/5 peer-checked:shadow-lg peer-checked:shadow-primary/10">'
        . '<span class="flex items-center justify-between gap-3">'
        . '<span class="material-symbols-outlined text-primary">medical_services</span>'
        . '<span class="text-[10px] font-bold uppercase tracking-[0.12em] text-outline">Command Access</span>'
        . '</span>'
        . '<span class="mt-4 block text-sm font-semibold text-on-surface">Responder</span>'
        . '<span class="mt-2 block text-xs leading-relaxed text-on-surface-variant">Monitor live incidents, review severity, and coordinate emergency response actions.</span>'
        . '</span>'
        . '</label>'
        . '</div>'
        . '</div>';

    $html = str_replace(
        '<form class="space-y-6">',
        '<form class="space-y-6" action="'.route('register').'" method="POST">'.csrf_field(),
        $html
    );

    $html = str_replace(
        '<div class="mb-10">',
        '<div class="mb-10">'.$message,
        $html
    );

    $html = str_replace(
        '<title>Vigilant Sentinel | Register</title>',
        $pwaHead.'<title>'.$shortBrand.' | Register</title>',
        $html
    );

    $html = str_replace(
        '<h1 class="text-xl font-black uppercase tracking-widest text-surface">Vigilant Sentinel</h1>',
        '<div class="space-y-3"><h1 class="text-xl font-black uppercase tracking-[0.14em] text-surface">'.$shortBrand.'</h1><p class="text-[11px] font-semibold leading-relaxed tracking-[0.08em] uppercase text-surface-dim max-w-sm">'.$systemName.'</p></div>',
        $html
    );

    $html = str_replace(
        '<h2 class="text-4xl font-headline font-bold tracking-tight mb-4 leading-tight">Secure Perimeter Protocols</h2>',
        '<h2 class="text-4xl font-headline font-bold tracking-tight mb-4 leading-tight">Emergency Access Setup</h2>',
        $html
    );

    $html = str_replace(
        '<p class="text-surface-dim font-light leading-relaxed max-w-xs">Initialize operator credentials to gain access to LoRa-mesh emergency telemetry and system diagnostics.</p>',
        '<p class="text-surface-dim font-light leading-relaxed max-w-sm">Create secure access for civilians and responders using AI severity review, LoRa-backed emergency reporting, and intelligent location monitoring across Bontoc Rescue operations.</p>',
        $html
    );

    $html = str_replace(
        '<h3 class="text-3xl font-headline font-bold text-on-surface mb-2">Operator Registration</h3>',
        '<h3 class="text-3xl font-headline font-bold text-on-surface mb-2">Emergency Account Registration</h3>',
        $html
    );

    $html = str_replace(
        '<p class="text-on-surface-variant font-medium text-sm">Create an authorized sentinel account</p>',
        '<p class="text-on-surface-variant font-medium text-sm">Create a civilian or responder account</p>',
        $html
    );

    $html = str_replace(
        'placeholder="Commander Shepard" type="text"/>',
        'name="name" value="'.e(old('name', '')).'" placeholder="Commander Shepard" required autocomplete="name" type="text"/>',
        $html
    );

    $html = str_replace(
        'placeholder="+1 (555) 000-0000" type="tel"/>',
        'name="phone" value="'.e(old('phone', '')).'" placeholder="+1 (555) 000-0000" required autocomplete="tel" type="tel"/>',
        $html
    );

    $html = str_replace(
        'placeholder="operator@vigilant-sentinel.io" type="email"/>',
        'name="email" value="'.e(old('email', '')).'" placeholder="responder@bontoc-rescue.local" required autocomplete="username" type="email"/>',
        $html
    );

    $html = preg_replace(
        '/<div class="grid grid-cols-1 sm:grid-cols-2 gap-6">\s*<!-- Password -->/',
        $roleField.'<div class="grid grid-cols-1 sm:grid-cols-2 gap-6">'."\n".'<!-- Password -->',
        $html,
        1
    );

    $passwordFieldIndex = 0;
    $html = preg_replace_callback(
        '/<input([^>]*?)type="password"([^>]*)\/>/',
        static function (array $matches) use (&$passwordFieldIndex): string {
            $fieldName = $passwordFieldIndex === 0 ? 'password' : 'password_confirmation';
            $fieldId = $fieldName;
            $passwordFieldIndex++;

            return '<input'.$matches[1].'id="'.$fieldId.'" name="'.$fieldName.'" required minlength="6" autocomplete="new-password" type="password" data-auth-password-input'.$matches[2].'/><button type="button" data-auth-toggle-target="'.$fieldId.'" aria-pressed="false" class="absolute right-0 bottom-2 text-[10px] font-bold uppercase tracking-[0.08em] text-primary hover:text-on-secondary-fixed-variant transition-colors">Show</button>';
        },
        $html,
        2
    );

    $html = str_replace(
        'Ensure your contact number matches your registered emergency response device for auto-calibration.',
        'Choose responder for live coordination access, or civilian for field reporting with GPS and evidence capture. Passwords only need a minimum of 6 characters.',
        $html
    );

    $html = str_replace(
        'Min. 12 characters required',
        'Min. 6 characters required',
        $html
    );

    $html = str_replace(
        'Register Operator',
        'Create Account',
        $html
    );

    $html = str_replace(
        'Already authorized?',
        'Already registered?',
        $html
    );

    $html = str_replace(
        '<a class="text-primary font-bold hover:underline underline-offset-4 ml-1" href="#">Log in to Sentinel</a>',
        '<a class="text-primary font-bold hover:underline underline-offset-4 ml-1" href="'.route('login').'">Log in</a>',
        $html
    );

    $html = str_replace(
        '<span>Â© 2024 VIGILANT SENTINEL</span>',
        '<span class="inline-block max-w-xl leading-relaxed">&copy; 2026 '.$shortBrand.' â€¢ '.$systemName.'</span>',
        $html
    );

    $html = str_replace(
        '<a class="hover:text-primary transition-colors" href="#">Privacy Protocols</a>',
        '<a class="hover:text-primary transition-colors" href="'.route('welcome').'">Return To Welcome</a>',
        $html
    );

    $html = str_replace(
        '<a class="hover:text-primary transition-colors" href="#">Terms of Operations</a>',
        '<a class="hover:text-primary transition-colors" href="'.route('login').'">Login</a>',
        $html
    );

    $html = str_replace('</body>', $pwaScript.$authHelperScript.'</body>', $html);

    echo $html;
@endphp
