@php
    $html = file_get_contents(base_path('../01_onboarding/welcome_screen/code.html'));
    $shortBrand = 'Bontoc Rescue';
    $systemName = 'AI-Powered LoRa Emergency Response and Severity Detection System with Intelligent Location Monitoring';
    $pwaHead = view('partials.pwa-head')->render();
    $pwaScript = '<script src="'.asset('pwa-helper.js').'"></script>';

    $html = str_replace(
        '<title>Vigilant Sentinel | AI-Powered Emergency Response</title>',
        $pwaHead.'<title>'.$shortBrand.' | Emergency Response</title>',
        $html
    );

    $html = str_replace(
        '<h1 class="text-lg font-black uppercase tracking-widest text-[#D32F2F]">Vigilant Sentinel</h1>',
        '<div class="space-y-3"><h1 class="text-lg font-black uppercase tracking-[0.14em] text-[#D32F2F]">'.$shortBrand.'</h1><p class="text-[11px] font-semibold uppercase tracking-[0.08em] leading-relaxed text-[#0d1e25] max-w-md">'.$systemName.'</p></div>',
        $html
    );

    $html = str_replace(
        'The next generation of disaster response. Harnessing decentralized <span class="text-on-surface font-bold">LoRa mesh networks</span> and predictive AI to maintain critical communication when the grid fails.',
        'A connected emergency platform for civilians and responders, combining <span class="text-on-surface font-bold">LoRa-backed reporting</span>, AI severity detection, and intelligent location monitoring when every second matters.',
        $html
    );

    $html = str_replace(
        '<span class="text-[10px] uppercase tracking-[0.1em] font-bold text-on-surface-variant">Protocol v9.4 Active</span>',
        '<span class="text-[10px] uppercase tracking-[0.1em] font-bold text-on-surface-variant">Emergency Grid Active</span>',
        $html
    );

    $html = str_replace(
        '<span class="text-[10px] uppercase tracking-widest font-bold">Sentinel AI</span>',
        '<span class="text-[10px] uppercase tracking-widest font-bold">Severity AI</span>',
        $html
    );

    $html = str_replace(
        '"Evacuation route Alpha-9 showing 15% increased congestion. Suggesting reroute via Sector 4."',
        '"Incoming crash analysis marked Serious. Dispatch nearest responder and keep LoRa fallback active."',
        $html
    );

    $cta = '<div class="flex flex-wrap gap-4 pt-4">'
        . '<a href="'.route('register').'" class="bg-gradient-to-br from-primary to-primary-container text-on-primary px-8 py-4 rounded-md font-bold tracking-tight shadow-lg shadow-primary/20 hover:scale-105 active:scale-95 transition-all flex items-center gap-3">'
        . 'Get Started'
        . '<span class="material-symbols-outlined text-sm" data-icon="arrow_forward">arrow_forward</span>'
        . '</a>'
        . '<div class="flex gap-2">'
        . '<a href="'.route('login').'" class="bg-secondary text-on-secondary px-6 py-4 rounded-md font-bold glass-panel hover:bg-secondary/90 transition-all inline-flex items-center justify-center">Login</a>'
        . '<a href="'.route('register').'" class="text-primary font-bold px-6 py-4 hover:bg-surface-container-low transition-colors rounded-md border border-outline-variant/15 inline-flex items-center justify-center">Register</a>'
        . '</div>'
        . '</div>';

    $html = preg_replace('/<div class="flex flex-wrap gap-4 pt-4">.*?<\/div>\s*<\/div>/s', $cta.'</div>', $html, 1);

    $html = str_replace(
        '<a href="#" class="font-Inter font-bold tracking-tight text-[#D32F2F] hover:bg-[#e7f6ff] transition-colors px-2 py-1 rounded">Home</a>',
        '<a href="'.route('welcome').'" class="font-Inter font-bold tracking-tight text-[#D32F2F] hover:bg-[#e7f6ff] transition-colors px-2 py-1 rounded">Home</a>',
        $html
    );

    $html = str_replace(
        '<a href="#" class="font-Inter font-bold tracking-tight text-[#0d1e25] dark:text-slate-400 hover:bg-[#e7f6ff] transition-colors px-2 py-1 rounded">Capabilities</a>',
        '<a href="'.route('login').'" class="font-Inter font-bold tracking-tight text-[#0d1e25] dark:text-slate-400 hover:bg-[#e7f6ff] transition-colors px-2 py-1 rounded">Login</a>',
        $html
    );

    $html = str_replace(
        '<a href="#" class="font-Inter font-bold tracking-tight text-[#0d1e25] dark:text-slate-400 hover:bg-[#e7f6ff] transition-colors px-2 py-1 rounded">Documentation</a>',
        '<a href="'.route('register').'" class="font-Inter font-bold tracking-tight text-[#0d1e25] dark:text-slate-400 hover:bg-[#e7f6ff] transition-colors px-2 py-1 rounded">Register</a>',
        $html
    );

    $mobileNav = '<nav class="md:hidden fixed bottom-0 left-0 w-full flex justify-around items-center px-4 pb-6 pt-2 bg-[#f4faff]/70 backdrop-blur-xl z-50 shadow-[0_-4px_40px_0_rgba(13,30,37,0.06)]">'
        . '<a class="flex flex-col items-center justify-center text-[#D32F2F] bg-[#ffffff] rounded-lg px-3 py-1" href="'.route('welcome').'">'
        . '<span class="material-symbols-outlined" data-icon="grid_view">grid_view</span><span class="font-Inter text-[10px] uppercase tracking-[0.05em] font-bold">Welcome</span></a>'
        . '<a class="flex flex-col items-center justify-center text-[#0d1e25] opacity-60" href="'.route('login').'">'
        . '<span class="material-symbols-outlined" data-icon="login">login</span><span class="font-Inter text-[10px] uppercase tracking-[0.05em] font-bold">Login</span></a>'
        . '<a class="flex flex-col items-center justify-center text-[#0d1e25] opacity-60" href="'.route('register').'">'
        . '<span class="material-symbols-outlined" data-icon="person_add">person_add</span><span class="font-Inter text-[10px] uppercase tracking-[0.05em] font-bold">Register</span></a>'
        . '</nav>';

    $html = preg_replace('/<nav class="md:hidden fixed bottom-0.*?<\/nav>/s', $mobileNav, $html, 1);
    $html = str_replace('</body>', $pwaScript.'</body>', $html);

    echo $html;
@endphp
