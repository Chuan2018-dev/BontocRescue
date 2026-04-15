@php
    $html = file_get_contents(base_path('../02_auth/login_screen/code.html'));
    $shortBrand = 'Bontoc Rescue';
    $systemName = 'AI-Powered LoRa Emergency Response and Severity Detection System with Intelligent Location Monitoring';
    $pwaHead = view('partials.pwa-head')->render();
    $pwaScript = '<script src="'.asset('pwa-helper.js').'"></script>';
    $authHelperScript = '<script src="'.asset('auth-password.js').'"></script>';

    $message = '';
    if ($errors->any()) {
        $message = '<div class="mb-6 rounded-lg bg-error-container px-4 py-3 text-sm font-semibold text-on-error-container">'
            . e($errors->first())
            . '</div>';
    }
    if (session('status')) {
        $message .= '<div class="mb-6 rounded-lg bg-surface-container-high px-4 py-3 text-sm font-semibold text-secondary">'
            . e(session('status'))
            . '</div>';
    }

    $html = str_replace(
        '<title>Vigilant Sentinel | Secure Access</title>',
        $pwaHead.'<title>'.$shortBrand.' | Secure Access</title>',
        $html
    );

    $html = str_replace(
        '<h1 class="font-headline font-black uppercase tracking-widest text-lg text-primary">Vigilant Sentinel</h1>',
        '<div class="space-y-3"><h1 class="font-headline font-black uppercase tracking-[0.14em] text-lg text-primary">'.$shortBrand.'</h1><p class="text-[11px] font-semibold uppercase tracking-[0.08em] leading-relaxed text-on-surface-variant max-w-xs">'.$systemName.'</p></div>',
        $html
    );

    $html = str_replace(
        '<p class="text-on-surface-variant text-sm font-medium">Authentication required for Sentinel Operator ID.</p>',
        '<p class="text-on-surface-variant text-sm font-medium">Authentication required for civilian and responder emergency accounts.</p>',
        $html
    );

    $html = str_replace(
        'Operator Identity',
        'Account Identity',
        $html
    );

    $html = str_replace(
        '<div class="surface-container-lowest glass-panel rounded-xl p-8 shadow-[0_4px_40px_0_rgba(13,30,37,0.06)]">',
        '<div class="surface-container-lowest glass-panel rounded-xl p-8 shadow-[0_4px_40px_0_rgba(13,30,37,0.06)]">'.$message,
        $html
    );

    $html = str_replace(
        '<form action="#" class="flex flex-col gap-6" method="POST">',
        '<form action="'.route('login').'" class="flex flex-col gap-6" method="POST">'.csrf_field(),
        $html
    );

    $html = str_replace(
        'id="identity" name="identity" placeholder="Email or ID Number" required="" type="text"/>',
        'id="identity" name="identity" value="'.e(old('identity', '')).'" placeholder="Email or ID Number" required type="email" autofocus autocomplete="username"/>',
        $html
    );

    $html = preg_replace(
        '/<input class="w-full pl-8 pr-4 py-3 bg-transparent border-b-2 border-outline-variant\/30 focus:border-primary focus:ring-0 text-on-surface placeholder:text-outline transition-all font-medium" id="password" name="password" placeholder=".*?" required="" type="password"\/>/',
        '<input class="w-full pl-8 pr-20 py-3 bg-transparent border-b-2 border-outline-variant/30 focus:border-primary focus:ring-0 text-on-surface placeholder:text-outline transition-all font-medium" id="password" name="password" placeholder="********" required minlength="6" type="password" autocomplete="current-password" data-auth-password-input/><button type="button" data-auth-toggle-target="password" aria-pressed="false" class="absolute right-0 top-1/2 -translate-y-1/2 text-xs font-bold uppercase tracking-[0.08em] text-secondary hover:text-on-secondary-fixed-variant transition-colors">Show</button>',
        $html,
        1
    );

    $html = str_replace(
        '</div>'."\n".'</div>'."\n".'<!-- Remember Me -->',
        '</div><p class="text-[11px] font-semibold text-on-surface-variant">Minimum of 6 characters.</p></div>'."\n".'<!-- Remember Me -->',
        $html
    );

    $html = str_replace(
        'id="remember" type="checkbox"/>',
        'id="remember" name="remember" value="1" '.(old('remember') ? 'checked' : '').' type="checkbox"/>',
        $html
    );

    $html = str_replace(
        '<a class="text-xs font-bold text-secondary hover:text-on-secondary-fixed-variant transition-colors" href="#">Forgot password</a>',
        '<a class="text-xs font-bold text-secondary hover:text-on-secondary-fixed-variant transition-colors" href="'.route('register').'">Need access? Register</a>',
        $html
    );

    $footer = '<footer class="flex flex-col items-center gap-8">'
        . '<div class="flex items-center gap-6">'
        . '<div class="flex items-center gap-2 px-3 py-1 bg-surface-container-high rounded-sm"><div class="w-1.5 h-1.5 rounded-full bg-secondary"></div><span class="font-label text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">LoRa Active</span></div>'
        . '<div class="flex items-center gap-2 px-3 py-1 bg-surface-container-high rounded-sm"><div class="w-1.5 h-1.5 rounded-full bg-primary-container"></div><span class="font-label text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Secure Uplink</span></div>'
        . '</div>'
        . '<p class="text-xs text-on-surface-variant">No account yet? <a class="text-primary font-bold hover:underline underline-offset-4" href="'.route('register').'">Register an account</a></p>'
        . '</footer>';

    $html = preg_replace('/<footer class="flex flex-col items-center gap-8">.*?<\/footer>/s', $footer, $html, 1);

    $html = str_replace('</body>', $pwaScript.$authHelperScript.'</body>', $html);

    echo $html;
@endphp
