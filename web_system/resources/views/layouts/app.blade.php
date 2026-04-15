<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('partials.pwa-head')
    <title>@yield('title', 'Operations Center') | Stitch Rescue Web</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root{color-scheme:light;--bg:#eef5fb;--surface:#fff;--surface-alt:#f7fbff;--surface-muted:#e7f0f7;--ink:#0f1f2f;--muted:#627181;--line:rgba(15,31,47,.10);--line-strong:rgba(15,31,47,.16);--accent:#c91c21;--accent-strong:#9f1318;--blue:#2068ae;--blue-soft:#dcebff;--green:#1f9d68;--green-soft:#dcf8e9;--amber:#d18b1f;--amber-soft:#fff1d4;--danger:#c72626;--danger-soft:#ffe2e2;--shadow:0 24px 60px rgba(12,25,39,.10);--shadow-soft:0 14px 34px rgba(12,25,39,.06);--radius-xl:30px;--radius-lg:22px;--sidebar-width:280px}
        *{box-sizing:border-box}html{scroll-behavior:smooth}
        body{margin:0;min-height:100vh;color:var(--ink);font-family:"Trebuchet MS","Aptos",sans-serif;background:radial-gradient(circle at top left,rgba(255,255,255,.95),transparent 26%),radial-gradient(circle at 88% 12%,rgba(201,28,33,.10),transparent 24%),linear-gradient(180deg,#f8fbff 0%,#edf4fb 34%,#e5edf4 100%)}
        a{color:inherit;text-decoration:none}button,input,select,textarea{font:inherit}
        .ops-shell{display:grid;grid-template-columns:var(--sidebar-width) minmax(0,1fr);min-height:100vh}.ops-sidebar{position:sticky;top:0;height:100vh;padding:28px 22px 22px;background:rgba(255,255,255,.74);border-right:1px solid var(--line);backdrop-filter:blur(18px);display:grid;grid-template-rows:auto auto minmax(0,1fr) auto;gap:24px;overflow-y:auto;overflow-x:hidden;overscroll-behavior:contain;-webkit-overflow-scrolling:touch;scrollbar-gutter:stable}.ops-main{padding:24px;display:grid;gap:20px;min-width:0;position:relative}
        .brand-lockup,.sidebar-cluster,.sidebar-foot,.stack,.incident-stack,.timeline,.alert-list,.content-stack,.panel-heading,.hero-copy{display:grid;gap:12px}.brand-lockup span,.topbar-label,.sidebar-label,.eyebrow,.panel-kicker,.metric-card span,.system-card span,.field label,.form-note{font-size:.74rem;font-weight:800;letter-spacing:.16em;text-transform:uppercase}
        .brand-lockup span{display:inline-flex;align-items:center;gap:8px;color:var(--accent)}.brand-lockup span::before{content:"";width:10px;height:10px;border-radius:999px;background:var(--accent);box-shadow:0 0 0 6px rgba(201,28,33,.10)}
        .brand-lockup strong,.topbar-copy h1,.hero-copy h2,.panel-title,.incident-title,.alert-top h3,.media-viewer-top strong,.metric-card strong{font-family:"Bahnschrift","Trebuchet MS",sans-serif}
        .brand-lockup strong{font-size:1.32rem;line-height:1.2;letter-spacing:-.03em}.brand-lockup p,.hero-copy p,.topbar-copy span,.metric-card p,.panel p,.preview-card p,.timeline-copy p,.detail-copy,.incident-summary,.system-card p,.alert-item p,.input,.textarea,select,.muted{color:var(--muted)}
        .sidebar-label strong{color:var(--ink)}.sidebar-nav{display:grid;gap:8px}.sidebar-nav a{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 16px;border-radius:18px;border:1px solid transparent;color:var(--muted);font-weight:700;background:transparent;transition:transform .2s ease,border-color .2s ease,background .2s ease,color .2s ease}.sidebar-nav a:hover,.sidebar-nav a:focus-visible{transform:translateX(2px);background:rgba(255,255,255,.76);border-color:var(--line);color:var(--ink)}.sidebar-nav a.active{background:#fff;border-color:rgba(201,28,33,.18);color:var(--accent);box-shadow:var(--shadow-soft)}.sidebar-nav small{display:inline-flex;min-width:28px;min-height:28px;align-items:center;justify-content:center;border-radius:999px;background:rgba(15,31,47,.06);font-size:.72rem;font-weight:800}.sidebar-nav a.active small{background:rgba(201,28,33,.12);color:var(--accent)}
        .sidebar-status-card{padding:18px;border-radius:22px;background:linear-gradient(180deg,rgba(15,31,47,.98),rgba(21,47,68,.92));color:#f7fbff;box-shadow:var(--shadow-soft);display:grid;gap:10px;overflow:hidden;position:relative}.sidebar-status-card::after{content:"";position:absolute;inset:auto -20% -30% auto;width:160px;height:160px;background:radial-gradient(circle,rgba(255,255,255,.12),transparent 62%)}.status-chip{display:inline-flex;align-items:center;gap:8px;font-size:.78rem;font-weight:800;letter-spacing:.14em;text-transform:uppercase}.status-chip::before{content:"";width:9px;height:9px;border-radius:999px;background:#38d39f;box-shadow:0 0 0 5px rgba(56,211,159,.16)}.sidebar-logout{display:grid}.sidebar-logout button{width:100%;justify-content:space-between}
        .topbar,.hero-card,.panel,.metric-card,.system-card,.flash{background:rgba(255,255,255,.78);border:1px solid var(--line);border-radius:var(--radius-xl);box-shadow:var(--shadow-soft);backdrop-filter:blur(18px)}.topbar{padding:18px 22px;display:flex;align-items:center;justify-content:space-between;gap:18px}.topbar-copy{display:grid;gap:8px;min-width:0}.topbar-copy p,.topbar-copy h1,.topbar-copy span,.alert-top p,.alert-top h3{margin:0}.topbar-label,.eyebrow{color:var(--accent)}.topbar-copy h1{font-size:clamp(2rem,3vw,3.4rem);line-height:.95;letter-spacing:-.05em}.topbar-actions,.hero-actions,.button-row,.tag-row,.panel-actions,.preview-grid,.action-row{display:flex;flex-wrap:wrap;gap:12px;align-items:center}
        .pill-btn,.mini-chip,.action-btn,.btn{display:inline-flex;align-items:center;justify-content:center;gap:10px;min-height:46px;padding:0 18px;border-radius:999px;border:1px solid transparent;cursor:pointer;transition:transform .2s ease,box-shadow .2s ease,border-color .2s ease,background .2s ease}.pill-btn:hover,.action-btn:hover,.btn:hover{transform:translateY(-1px)}.pill-btn{font-weight:800;color:var(--ink);background:#fff;border-color:var(--line)}.pill-btn strong{font-size:.82rem;color:var(--accent)}.mini-chip{min-height:40px;padding:0 14px;background:var(--surface-alt);border-color:var(--line);color:var(--muted);font-size:.82rem;font-weight:700}
        .identity-card{display:inline-flex;align-items:center;gap:12px;padding:8px 10px 8px 8px;border-radius:999px;background:#fff;border:1px solid var(--line)}.identity-card strong,.identity-card span{display:block}.identity-card strong{font-size:.9rem}.identity-card span{font-size:.76rem;color:var(--muted);font-weight:700;letter-spacing:.10em;text-transform:uppercase}.avatar{width:44px;height:44px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#153c5e,#1f6ca8);color:#fff;font-size:.9rem;font-weight:900;letter-spacing:.08em;overflow:hidden}.avatar img{width:100%;height:100%;object-fit:cover;display:block}
        .system-strip{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:12px}.system-card{padding:16px 18px;display:grid;gap:8px}.system-card strong{font-size:.95rem;line-height:1.4}.system-card p{margin:0;font-size:.84rem}.system-dot{display:inline-flex;align-items:center;gap:8px}.system-dot::before{content:"";width:10px;height:10px;border-radius:999px;background:var(--green);box-shadow:0 0 0 6px rgba(31,157,104,.12)}.system-dot.warning::before{background:var(--amber);box-shadow:0 0 0 6px rgba(209,139,31,.14)}
        .hero-card{padding:28px;display:grid;gap:24px}.hero-grid{display:grid;grid-template-columns:minmax(0,1.2fr) minmax(300px,.8fr);gap:24px;align-items:start}.hero-copy h2{margin:0;font-size:clamp(2rem,2.8vw,3rem);line-height:1.02;letter-spacing:-.05em}.hero-copy p,.panel p{margin:0;line-height:1.75}.btn,.action-btn{font-weight:800}.btn-primary,.action-btn.primary{background:linear-gradient(135deg,var(--accent),var(--accent-strong));color:#fff;box-shadow:0 18px 30px rgba(201,28,33,.22)}.btn-secondary,.action-btn.secondary{background:#fff;border-color:rgba(32,104,174,.16);color:var(--blue)}.btn-danger,.action-btn.danger{background:#fff;border-color:rgba(199,38,38,.20);color:var(--danger)}
        .hero-metrics,.metric-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.metric-card{padding:20px;display:grid;gap:10px;min-height:150px}.metric-card strong{font-size:2.4rem;line-height:1;letter-spacing:-.06em}.flash{padding:16px 18px;color:var(--blue);font-weight:700}.flash.error{color:var(--danger)}.panel{padding:24px;min-width:0}.panel-head{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;margin-bottom:18px}.panel-title{margin:0;font-size:1.55rem;line-height:1.05;letter-spacing:-.04em}.section-copy{margin:0;line-height:1.7;color:var(--muted)}
        .dual-grid{display:grid;grid-template-columns:minmax(0,1.18fr) minmax(320px,.82fr);gap:20px}.triple-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:20px}.view-switch{display:inline-flex;align-items:center;gap:6px;padding:6px;border-radius:999px;background:var(--surface-alt);border:1px solid var(--line)}.view-switch button{border:0;background:transparent;color:var(--muted);font-size:.82rem;font-weight:800;letter-spacing:.10em;text-transform:uppercase;padding:12px 14px;border-radius:999px;cursor:pointer}.view-switch button.is-active{background:#fff;color:var(--ink);box-shadow:var(--shadow-soft)}.monitor-shell{display:grid;grid-template-columns:minmax(0,1.25fr) minmax(320px,.75fr);gap:20px}.monitor-shell[data-view-mode="list"]{grid-template-columns:1fr}.monitor-shell[data-view-mode="list"] [data-view-panel="map"]{display:none}.monitor-shell[data-view-mode="map"]{grid-template-columns:1fr}.monitor-shell[data-view-mode="map"] [data-view-panel="list"]{display:none}
        .summary-strip{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px}.summary-card{padding:18px;border-radius:20px;background:var(--surface-alt);border:1px solid rgba(15,31,47,.08);display:grid;gap:8px}.summary-card strong{font-size:1.6rem;line-height:1;letter-spacing:-.04em}.summary-card p{margin:0;font-size:.9rem;line-height:1.55}.workspace-grid{display:grid;grid-template-columns:minmax(0,1.4fr) minmax(320px,.8fr);gap:20px}.workspace-side,.meta-list,.settings-grid,.toggle-stack{display:grid;gap:14px}.panel-note{padding:16px 18px;border-radius:18px;background:linear-gradient(135deg,rgba(255,255,255,.98),rgba(241,247,253,.96));border:1px solid rgba(15,31,47,.08)}.meta-row{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;padding:12px 0;border-bottom:1px solid rgba(15,31,47,.08)}.meta-row:last-child{border-bottom:0;padding-bottom:0}.meta-row span{color:var(--muted);font-size:.9rem;line-height:1.55}.meta-row strong{display:block;font-size:.95rem;line-height:1.45;color:var(--ink)}.subtle{font-size:.88rem;color:var(--muted)}.panel-toolbar{display:flex;flex-wrap:wrap;gap:12px;align-items:center;justify-content:space-between}.toolbar-actions{display:flex;flex-wrap:wrap;gap:12px;align-items:center}.settings-grid{grid-template-columns:repeat(auto-fit,minmax(220px,1fr))}.setting-card{padding:18px;border-radius:20px;background:var(--surface-alt);border:1px solid rgba(15,31,47,.08);display:grid;gap:12px}.setting-card h3,.setting-card strong{margin:0;font-size:1rem;line-height:1.4}.toggle-item{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;padding:14px 16px;border-radius:18px;background:#fff;border:1px solid rgba(15,31,47,.08)}.toggle-copy{display:grid;gap:6px}.toggle-copy p{margin:0;font-size:.88rem;line-height:1.55}.switch{position:relative;display:inline-flex;align-items:center;min-width:52px;height:32px}.switch input{position:absolute;opacity:0;inset:0;cursor:pointer}.switch-track{width:52px;height:32px;border-radius:999px;background:rgba(15,31,47,.14);position:relative;transition:background .2s ease}.switch-track::after{content:"";position:absolute;top:4px;left:4px;width:24px;height:24px;border-radius:50%;background:#fff;box-shadow:0 4px 12px rgba(15,31,47,.18);transition:transform .2s ease}input:checked + .switch-track{background:linear-gradient(135deg,var(--accent),var(--accent-strong))}input:checked + .switch-track::after{transform:translateX(20px)}
        .incident-card{display:grid;grid-template-columns:8px minmax(160px,.25fr) minmax(0,1fr);gap:18px;padding:18px;border-radius:24px;background:#fff;border:1px solid rgba(15,31,47,.08);box-shadow:var(--shadow-soft)}.incident-card.compact-live-card{grid-template-columns:8px minmax(140px,.24fr) minmax(0,1fr)}.incident-rail{border-radius:999px;min-height:100%;background:var(--blue)}.incident-rail.severity-Fatal{background:var(--danger)}.incident-rail.severity-Serious{background:var(--amber)}.incident-rail.severity-Minor{background:var(--green)}.incident-meta{display:grid;align-content:start;gap:10px}.incident-code{font-size:.72rem;font-weight:800;letter-spacing:.16em;text-transform:uppercase;color:var(--muted)}.incident-time,.incident-priority{font-size:1.1rem;font-weight:900;line-height:1.2;letter-spacing:-.03em}.tag,.badge,.severity-chip{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:7px 12px;border-radius:999px;font-size:.72rem;font-weight:900;letter-spacing:.14em;text-transform:uppercase}.tag.neutral,.badge.neutral{background:var(--surface-muted);color:var(--ink)}.tag.blue,.badge.blue{background:var(--blue-soft);color:var(--blue)}.tag.green,.badge.green,.badge.success{background:var(--green-soft);color:var(--green)}.tag.amber,.badge.amber{background:var(--amber-soft);color:var(--amber)}.tag.red,.badge.red,.badge.critical{background:var(--danger-soft);color:var(--danger)}
        .incident-content{display:grid;gap:14px}.incident-headline{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;flex-wrap:wrap}.incident-title{margin:0;font-size:1.55rem;line-height:1.08;letter-spacing:-.04em}.detail-copy{font-size:.96rem;line-height:1.75}.detail-copy strong{color:var(--ink)}.incident-summary{margin:0;font-size:.96rem;line-height:1.75}.preview-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr))}.preview-card,.detail-card{padding:16px;border-radius:20px;background:var(--surface-alt);border:1px solid rgba(15,31,47,.08);display:grid;gap:12px}.preview-card strong,.detail-card strong{font-size:.96rem}.preview-card img,.preview-card video,.preview-thumb,.detail-media{display:block;width:100%;border-radius:18px;background:#102332;object-fit:cover}.preview-thumb{max-height:172px}.detail-media{max-height:420px}
        .media-trigger{padding:0;border:0;background:transparent;cursor:zoom-in;text-align:left;color:inherit;display:grid;gap:10px}.media-trigger span{font-size:.78rem;font-weight:800;color:var(--blue)}.form-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:14px}.field{display:grid;gap:8px}.field label{font-size:.82rem;font-weight:800;letter-spacing:.10em;text-transform:uppercase}.input,.textarea,select{width:100%;min-height:50px;padding:14px 16px;border-radius:18px;border:1px solid var(--line-strong);background:#fff}.textarea{min-height:140px;resize:vertical}.divider{height:1px;background:var(--line);margin:4px 0}.incident-actions{display:grid;gap:14px}.action-row form{display:contents}
        .map-shell{overflow:hidden}.map-canvas{width:100%;min-height:420px;border-radius:24px;overflow:hidden;border:1px solid rgba(15,31,47,.08)}.leaflet-container{font:inherit}.timeline-item{display:grid;grid-template-columns:16px minmax(0,1fr);gap:14px;align-items:start}.timeline-marker{width:16px;height:16px;border-radius:999px;background:var(--blue);box-shadow:0 0 0 8px rgba(32,104,174,.12);margin-top:6px}.timeline-copy{padding:16px 18px;border-radius:18px;background:var(--surface-alt);border:1px solid rgba(15,31,47,.08);display:grid;gap:8px}.timeline-copy .meta{font-size:.78rem;color:var(--muted);font-weight:700;letter-spacing:.08em;text-transform:uppercase}
        .alert-center{position:fixed;top:92px;right:26px;width:min(360px,calc(100vw - 30px));max-height:calc(100vh - 120px);padding:18px;border-radius:28px;background:rgba(255,255,255,.94);border:1px solid var(--line);box-shadow:var(--shadow);backdrop-filter:blur(20px);z-index:1100;display:grid;gap:16px;overflow:auto}.alert-center[hidden]{display:none}.alert-top{display:flex;align-items:flex-start;justify-content:space-between;gap:16px}.alert-top h3{font-size:1.3rem;line-height:1.05;letter-spacing:-.04em}.alert-item{padding:14px 16px;border-radius:18px;background:var(--surface-alt);border:1px solid rgba(15,31,47,.08);display:grid;gap:8px}.alert-item strong{font-size:.98rem;line-height:1.35}.alert-item p{margin:0;font-size:.88rem;line-height:1.65}.data-empty{padding:20px;border-radius:22px;background:var(--surface-alt);border:1px dashed rgba(15,31,47,.16);color:var(--muted)}
        .live-toast-stack{position:fixed;top:24px;right:24px;display:grid;gap:10px;width:min(360px,calc(100vw - 30px));z-index:1300}.live-toast{background:rgba(15,31,47,.95);color:#fff;padding:16px 18px;border-radius:18px;box-shadow:var(--shadow);display:grid;gap:6px}.live-toast strong{font-size:.96rem}.media-viewer[hidden]{display:none}.media-viewer{position:fixed;inset:0;z-index:1500;background:rgba(15,31,47,.84);backdrop-filter:blur(14px);padding:24px}.media-viewer-shell{width:min(1120px,100%);max-height:calc(100vh - 48px);margin:0 auto;padding:22px;border-radius:28px;background:rgba(255,255,255,.98);box-shadow:var(--shadow);display:grid;gap:16px}.media-viewer-top{display:flex;align-items:flex-start;justify-content:space-between;gap:14px}.media-viewer-top strong{display:block;font-size:1.2rem;line-height:1.25;letter-spacing:-.03em}.media-viewer-close{width:46px;height:46px;border:0;border-radius:999px;background:rgba(15,31,47,.08);cursor:pointer;font-size:1.5rem}.media-viewer-frame{min-height:320px;max-height:calc(100vh - 180px);display:flex;align-items:center;justify-content:center;border-radius:24px;overflow:hidden;background:#102332}.media-viewer-frame img,.media-viewer-frame video{width:100%;max-height:calc(100vh - 200px);object-fit:contain;background:#102332}.sidebar-toggle,.sidebar-close,.sidebar-mobile-head{display:none}.sidebar-backdrop{display:none}
        .pwa-shell-pill{display:inline-flex;align-items:center;gap:10px;padding:11px 14px;border-radius:999px;background:rgba(255,255,255,.9);border:1px solid var(--line);font-size:.78rem;font-weight:900;letter-spacing:.10em;text-transform:uppercase;box-shadow:var(--shadow-soft)}.pwa-shell-pill strong{font-size:.78rem;color:var(--ink)}.pwa-shell-pill::before{content:"";width:10px;height:10px;border-radius:999px;background:var(--green);box-shadow:0 0 0 6px rgba(31,157,104,.12)}.pwa-shell-pill.is-offline::before{background:var(--amber);box-shadow:0 0 0 6px rgba(209,139,31,.14)}
        .pwa-install-card,.pwa-update-card{position:fixed;z-index:1350;display:grid;gap:14px;align-items:center;padding:18px 20px;border-radius:26px;background:rgba(15,31,47,.95);color:#fff;box-shadow:var(--shadow);backdrop-filter:blur(18px)}.pwa-install-card{left:24px;right:24px;bottom:24px;grid-template-columns:minmax(0,1fr) auto;max-width:880px;margin:0 auto}.pwa-update-card{top:24px;right:24px;left:auto;bottom:auto;width:min(340px,calc(100vw - 30px));padding:14px 16px;grid-template-columns:1fr;border-radius:22px;background:rgba(15,31,47,.92)}.pwa-install-card[hidden],.pwa-update-card[hidden]{display:none}.pwa-install-copy,.pwa-update-copy{display:grid;gap:8px}.pwa-install-copy p,.pwa-install-copy strong,.pwa-update-copy p,.pwa-update-copy strong{margin:0}.pwa-install-copy strong,.pwa-update-copy strong{font-size:1rem;letter-spacing:-.02em}.pwa-update-copy strong{font-size:.96rem}.pwa-install-copy p,.pwa-update-copy p{font-size:.92rem;line-height:1.65;color:rgba(255,255,255,.78)}.pwa-update-copy p{font-size:.82rem;line-height:1.5}.pwa-install-actions{display:flex;flex-wrap:wrap;gap:12px;align-items:center;justify-content:flex-end}.pwa-install-card .btn-secondary,.pwa-update-card .btn-secondary{background:rgba(255,255,255,.1);border-color:rgba(255,255,255,.18);color:#fff}.pwa-install-card .btn-primary,.pwa-update-card .btn-primary{box-shadow:none}.pwa-update-badge{display:inline-flex;align-items:center;gap:8px;font-size:.68rem;font-weight:900;letter-spacing:.12em;text-transform:uppercase;color:#fff}.pwa-update-badge::before{content:"";width:9px;height:9px;border-radius:999px;background:#38d39f;box-shadow:0 0 0 5px rgba(56,211,159,.15);animation:pwaPulse 1.2s ease-in-out infinite}
        @keyframes pwaPulse{0%,100%{transform:scale(1);opacity:1}50%{transform:scale(.78);opacity:.75}}
        @media (max-width:1220px){.ops-shell{display:block}.ops-sidebar{position:fixed;left:0;top:0;bottom:0;width:min(88vw,340px);height:100vh;padding:24px 18px 20px;transform:translateX(-105%);transition:transform .25s ease;z-index:1450;overflow-y:auto;overflow-x:hidden;background:rgba(255,255,255,.96);border-right:1px solid var(--line);box-shadow:var(--shadow)}.ops-sidebar.is-open{transform:translateX(0)}.ops-main{padding:18px}.sidebar-toggle,.sidebar-close{display:inline-flex}.sidebar-mobile-head{display:flex;align-items:center;justify-content:space-between;gap:12px}.sidebar-backdrop{display:block;position:fixed;inset:0;background:rgba(15,31,47,.36);backdrop-filter:blur(4px);border:0;z-index:1400}.sidebar-backdrop[hidden]{display:none}body.sidebar-open{overflow:hidden}}
        @media (max-width:1380px){.system-strip{grid-template-columns:repeat(3,minmax(0,1fr))}}@media (max-width:1220px){.hero-grid,.dual-grid,.monitor-shell,.triple-grid,.workspace-grid{grid-template-columns:1fr}}@media (max-width:860px){.ops-main{padding:18px}.topbar,.hero-card,.panel,.metric-card,.system-card{border-radius:24px}.topbar{flex-direction:column;align-items:stretch}.topbar-actions{justify-content:flex-start}.system-strip{grid-template-columns:repeat(2,minmax(0,1fr))}.hero-metrics,.metric-grid,.preview-grid,.summary-strip,.settings-grid{grid-template-columns:1fr}.incident-card,.incident-card.compact-live-card{grid-template-columns:8px 1fr}.incident-meta,.incident-content{grid-column:2}.action-row,.hero-actions,.button-row,.panel-actions,.toolbar-actions,.pwa-install-actions,.pwa-update-actions{flex-direction:column;align-items:stretch}.pill-btn,.action-btn,.btn,.mini-chip{width:100%}.alert-center{top:18px;right:18px;left:18px;width:auto}.brand-lockup p{display:none}.meta-row,.toggle-item{flex-direction:column;align-items:stretch}.pwa-install-card{left:18px;right:18px;grid-template-columns:1fr}.pwa-update-card{top:18px;right:18px;left:auto;bottom:auto;grid-template-columns:1fr;width:min(320px,calc(100vw - 36px))}.pwa-install-card{bottom:18px}}@media (max-width:620px){.ops-main{padding:14px}.system-strip{grid-template-columns:1fr}.view-switch{width:100%;justify-content:space-between}.view-switch button{flex:1}.identity-card{width:100%;justify-content:space-between}.media-viewer{padding:12px}.media-viewer-shell{padding:16px;border-radius:22px}.ops-sidebar{width:min(92vw,320px);padding:20px 14px 18px}.pwa-install-card{left:12px;right:12px;padding:16px}.pwa-update-card{top:12px;right:12px;left:auto;padding:14px;width:min(300px,calc(100vw - 24px))}.pwa-install-card{bottom:12px}}
    </style>
    <style>
        .capture-action-card{position:relative;cursor:pointer;background:linear-gradient(180deg,rgba(255,239,239,.98),rgba(255,228,228,.96));border:1px solid rgba(201,28,33,.18);box-shadow:0 10px 22px rgba(201,28,33,.08);transition:transform .2s ease,box-shadow .2s ease,border-color .2s ease}
        .capture-action-card:hover,.capture-action-card:focus-visible{transform:translateY(-2px);border-color:rgba(201,28,33,.32);box-shadow:0 16px 28px rgba(201,28,33,.12)}
        .capture-action-card strong{color:#8f1016}
        .capture-action-card p{color:#7d5a5a}
        .form-readiness-card{background:linear-gradient(180deg,rgba(255,250,250,.98),rgba(255,241,241,.96));border-color:rgba(201,28,33,.14)}
        .report-requirements-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px}
        .requirement-item{padding:16px;border-radius:18px;background:#fff;border:1px solid rgba(15,31,47,.08);display:grid;gap:8px}
        .requirement-item strong{font-size:.95rem}
        .btn[disabled],.action-btn[disabled],.pill-btn[disabled]{opacity:.58;cursor:not-allowed;transform:none;box-shadow:none}
        .mobile-quick-nav{display:none}
        @media (min-width:900px) and (max-width:1220px){
            body.is-standalone .ops-shell{display:grid;grid-template-columns:108px minmax(0,1fr)}
            body.is-standalone .ops-sidebar{position:sticky;left:auto;top:0;bottom:auto;width:auto;height:100vh;max-width:none;padding:18px 10px 14px;transform:none!important;box-shadow:none;z-index:auto}
            body.is-standalone .sidebar-mobile-head,body.is-standalone .sidebar-backdrop,body.is-standalone .sidebar-toggle{display:none!important}
            body.is-standalone .brand-lockup{justify-items:center;text-align:center}
            body.is-standalone .brand-lockup strong,body.is-standalone .brand-lockup p,body.is-standalone .sidebar-label,body.is-standalone .sidebar-status-card p,body.is-standalone .sidebar-status-card strong{display:none}
            body.is-standalone .brand-lockup span{font-size:.62rem;line-height:1.35;letter-spacing:.1em;justify-content:center}
            body.is-standalone .sidebar-nav a{justify-content:center;padding:14px 8px;min-height:58px}
            body.is-standalone .sidebar-nav a span{display:none}
            body.is-standalone .sidebar-nav small{min-width:38px;min-height:38px;font-size:.78rem}
            body.is-standalone .sidebar-status-card{padding:14px;justify-items:center}
            body.is-standalone .sidebar-status-card .status-chip{font-size:.64rem;justify-content:center}
            body.is-standalone .sidebar-logout button{padding:0 10px}
        }
        @media (max-width:1220px){
            .ops-main{padding-bottom:104px}
            .mobile-quick-nav{position:fixed;left:12px;right:12px;bottom:max(12px,env(safe-area-inset-bottom));z-index:1420;display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;padding:10px;border-radius:24px;background:rgba(255,255,255,.96);border:1px solid rgba(15,31,47,.10);box-shadow:0 18px 40px rgba(12,25,39,.16);backdrop-filter:blur(18px)}
            .mobile-quick-nav a{display:grid;gap:6px;justify-items:center;padding:10px 8px;border-radius:18px;color:var(--muted);font-size:.72rem;font-weight:800;letter-spacing:.05em;text-transform:uppercase}
            .mobile-quick-nav a strong{font-size:.72rem;line-height:1.2}
            .mobile-quick-nav a.active{background:rgba(201,28,33,.10);color:var(--accent)}
            .mobile-quick-nav span{display:inline-flex;min-width:34px;min-height:34px;align-items:center;justify-content:center;border-radius:999px;background:rgba(15,31,47,.06);font-size:.74rem;font-weight:900}
            .mobile-quick-nav a.active span{background:rgba(201,28,33,.14);color:var(--accent)}
        }
        @media (max-width:620px){
            .mobile-quick-nav{left:10px;right:10px;gap:8px;padding:8px}
            .mobile-quick-nav a{padding:8px 6px;font-size:.66rem}
            .mobile-quick-nav a strong{font-size:.66rem}
            .mobile-quick-nav span{min-width:30px;min-height:30px;font-size:.68rem}
            .pwa-update-card{top:14px;right:10px;width:min(300px,calc(100vw - 20px));padding:12px 14px}
        }
    </style>
</head>
<body data-pwa-shell>
    @php
        $authUser = auth()->user();
        $isCivilian = $authUser?->isCivilian() ?? false;
        $dashboardUrl = $authUser && $authUser->is_admin ? route('admin.dashboard') : route('dashboard');
        $monitoringUrl = $isCivilian ? route('dashboard') : route('monitoring');
        $roleLabel = $authUser?->is_admin ? 'Admin Responder' : ($authUser?->role === 'civilian' ? 'Civilian' : 'Responder');
        $stationLabel = $authUser?->responderProfile?->assigned_station ?? ($isCivilian ? 'Civilian Mobile' : 'Field Access');
        $pageLabel = trim($__env->yieldContent('page_label')) ?: ($isCivilian ? 'Civilian Dashboard' : 'Monitoring');
        $pageHeading = trim($__env->yieldContent('page_heading')) ?: (trim($__env->yieldContent('title')) ?: 'Operations Center');
        $pageSubheading = trim($__env->yieldContent('page_subheading')) ?: 'Live coordination, dispatch visibility, and incident intelligence.';
        $fatalAlertCount = isset($fatalAlerts) ? collect($fatalAlerts)->count() : 0;
        $compactPrimaryUrl = $authUser?->is_admin ? route('admin.dashboard') : ($isCivilian ? route('dashboard') : route('monitoring'));
        $compactPrimaryLabel = $authUser?->is_admin ? 'Admin' : ($isCivilian ? 'Home' : 'Monitor');
        $compactPrimaryCode = $authUser?->is_admin ? 'AD' : ($isCivilian ? 'HM' : 'MN');
        $compactSecondaryUrl = $isCivilian ? route('reports.create') : route('reports.index');
        $compactSecondaryLabel = $isCivilian ? 'Report' : 'Feed';
        $compactSecondaryCode = $isCivilian ? 'RP' : 'FD';
        $nameParts = preg_split('/\s+/', trim((string) $authUser?->name)) ?: [];
        $initials = collect($nameParts)->filter()->map(static fn (string $part): string => strtoupper(substr($part, 0, 1)))->take(2)->implode('');
        $initials = $initials !== '' ? $initials : 'SR';
        $authProfilePhotoUrl = $authUser?->profile_photo_path ? route('profile.photo') : null;
        $runtimeReverbHost = env('VITE_REVERB_HOST', env('REVERB_HOST', request()->getHost()));
        $runtimeReverbPort = (int) env('VITE_REVERB_PORT', env('REVERB_PORT', 443));
        $runtimeReverbScheme = env('VITE_REVERB_SCHEME', env('REVERB_SCHEME', 'https'));
    @endphp
    <script>
        window.__STITCH_RUNTIME__ = Object.assign({}, window.__STITCH_RUNTIME__ || {}, {
            reverb: {
                key: @json(env('VITE_REVERB_APP_KEY', env('REVERB_APP_KEY'))),
                host: @json($runtimeReverbHost),
                port: @json($runtimeReverbPort),
                scheme: @json($runtimeReverbScheme),
            },
        });
    </script>

    <button type="button" class="sidebar-backdrop" data-sidebar-backdrop hidden aria-label="Close navigation"></button>
    <div class="ops-shell">
        <aside class="ops-sidebar" data-sidebar>
            <div class="sidebar-mobile-head">
                <span class="sidebar-label">Operations Menu</span>
                <button type="button" class="pill-btn sidebar-close" data-sidebar-close>Close</button>
            </div>
            <a href="{{ $dashboardUrl }}" class="brand-lockup">
                <span>Bontoc Rescue</span>
                <strong>AI-Powered LoRa Emergency Response and Severity Detection System with Intelligent Location Monitoring</strong>
                <p>One command workspace for monitoring, civilian reporting, settings, and emergency coordination.</p>
            </a>

            <div class="sidebar-cluster">
                <div class="sidebar-label">Operations<br><strong>{{ $stationLabel }}</strong></div>
                <nav class="sidebar-nav">
                    <a href="{{ $monitoringUrl }}" class="{{ $isCivilian ? (request()->routeIs('dashboard') ? 'active' : '') : ((request()->routeIs('monitoring') || (request()->routeIs('dashboard') && ! $authUser?->is_admin)) ? 'active' : '') }}"><span>{{ $isCivilian ? 'Dashboard' : 'Monitoring' }}</span><small>{{ $isCivilian ? 'DB' : '01' }}</small></a>
                    @if ($isCivilian)
                        <a href="{{ route('reports.create') }}" class="{{ request()->routeIs('reports.create') ? 'active' : '' }}"><span>Send Report</span><small>SR</small></a>
                        <a href="{{ route('reports.index') }}" class="{{ request()->routeIs('reports.index') || request()->routeIs('reports.show') || request()->routeIs('reports.success') || request()->routeIs('reports.severity') || request()->routeIs('reports.transmissions') ? 'active' : '' }}"><span>Report History</span><small>{{ isset($stats['total']) ? $stats['total'] : 'RH' }}</small></a>
                    @else
                        <a href="{{ route('reports.index') }}" class="{{ request()->routeIs('reports.*') ? 'active' : '' }}"><span>Incident Feed</span><small>{{ isset($stats['active']) ? $stats['active'] : $fatalAlertCount }}</small></a>
                    @endif
                    @if ($authUser && ! $authUser->isCivilian())
                        <a href="{{ route('civilian-accounts.index') }}" class="{{ request()->routeIs('civilian-accounts.*') ? 'active' : '' }}"><span>Civilian Accounts</span><small>CA</small></a>
                    @endif
                    @if ($authUser?->is_admin)
                        <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.*') ? 'active' : '' }}"><span>Admin Board</span><small>AD</small></a>
                    @endif
                    <a href="{{ route('profile.show') }}" class="{{ request()->routeIs('profile.*') ? 'active' : '' }}"><span>{{ $isCivilian ? 'Civilian Profile' : 'Responder Profile' }}</span><small>{{ $isCivilian ? 'CP' : 'PR' }}</small></a>
                    <a href="{{ route('settings.index') }}" class="{{ request()->routeIs('settings.*') ? 'active' : '' }}"><span>System Settings</span><small>ST</small></a>
                </nav>
            </div>

            <div></div>

            <div class="sidebar-foot">
                <div class="sidebar-status-card">
                    <span class="status-chip">System Status Ready</span>
                    <strong>{{ $roleLabel }}</strong>
                    <p>{{ $isCivilian ? 'Civilian reporting access is ready for evidence capture, GPS tracking, live selfie verification, and emergency report history review.' : 'Emergency coordination channel armed for web monitoring, AI triage review, GPS tracking, and LoRa-backed fallback operations.' }}</p>
                </div>
                @if ($authUser)
                    <form method="POST" action="{{ route('logout') }}" class="sidebar-logout">
                        @csrf
                        <button type="submit" class="btn btn-danger">
                            <span>Log Out</span>
                            <strong>Exit</strong>
                        </button>
                    </form>
                @endif
            </div>
        </aside>

        <div class="ops-main">
            <div class="live-toast-stack" data-live-toast-stack></div>

            <header class="topbar">
                <div class="topbar-copy">
                    <p class="topbar-label">{{ $pageLabel }}</p>
                    <h1>{{ $pageHeading }}</h1>
                    <span>{{ $pageSubheading }}</span>
                </div>
                <div class="topbar-actions">
                    <button type="button" class="pill-btn sidebar-toggle" data-sidebar-toggle>Operations Menu</button>
                    <div class="pwa-shell-pill" data-pwa-status-pill><span data-pwa-status-label>Online Ready</span><strong data-pwa-display-mode>Browser</strong></div>
                    @unless ($isCivilian)
                        <button type="button" class="pill-btn" data-alert-center-toggle aria-expanded="false"><span>Active Alerts</span><strong data-alert-count>{{ $fatalAlertCount }}</strong></button>
                        <button type="button" class="mini-chip" data-enable-live-alerts>Enable Live Alerts</button>
                    @else
                        <a href="{{ route('reports.create') }}" class="mini-chip"><span>Quick Action</span><strong>Send Report</strong></a>
                    @endunless
                    <div class="mini-chip"><span>System Time</span><strong data-system-time>{{ now()->format('Y-m-d H:i:s') }}</strong></div>
                    @if ($authUser)
                        <div class="identity-card">
                            <div><strong>{{ $authUser->name }}</strong><span>{{ $roleLabel }}</span></div>
                            <div class="avatar">
                                @if ($authProfilePhotoUrl)
                                    <img src="{{ $authProfilePhotoUrl }}" alt="{{ $authUser->name }} profile photo">
                                @else
                                    {{ $initials }}
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </header>

            @if (isset($connectivity))
                <section class="system-strip">
                    <article class="system-card"><span>LoRa Status</span><strong class="system-dot">{{ $connectivity['lora_status'] ?? 'LoRa standby' }}</strong><p>Fallback mesh telemetry for compact incident transmission.</p></article>
                    <article class="system-card"><span>Internet Status</span><strong class="system-dot {{ str_contains(strtolower($connectivity['internet_status'] ?? ''), 'degraded') ? 'warning' : '' }}">{{ $connectivity['internet_status'] ?? 'Internet uplink healthy' }}</strong><p>Primary channel for full evidence payloads and responder sync.</p></article>
                    <article class="system-card"><span>Websocket / Reverb</span><strong class="system-dot">{{ $connectivity['websocket_status'] ?? 'Reverb live sync online' }}</strong><p>Real-time alert propagation to dashboards and responder views.</p></article>
                    <article class="system-card"><span>Queue Status</span><strong class="system-dot">{{ $connectivity['queue_status'] ?? 'Immediate broadcast channel' }}</strong><p>Incident updates and broadcast events are ready for dispatch.</p></article>
                    <article class="system-card"><span>Server / API</span><strong class="system-dot">{{ $connectivity['api_status'] ?? 'Laravel API healthy' }}</strong><p>Web dashboard and mobile API endpoint availability snapshot.</p></article>
                    <article class="system-card"><span>Gateway Sync</span><strong class="system-dot">{{ $connectivity['gateway_status'] ?? 'Gateway synchronized' }}</strong><p>Shared situational data across responder web and mobile interfaces.</p></article>
                </section>
            @endif

            @hasSection('hero')
                @yield('hero')
            @endif

            @if ($errors->any())
                <div class="flash error">{{ $errors->first() }}</div>
            @endif

            @if (session('status'))
                <div class="flash">{{ session('status') }}</div>
            @endif

            <main class="content-stack">
                @yield('content')
            </main>
        </div>
    </div>
    @if ($authUser)
        <nav class="mobile-quick-nav" aria-label="Quick navigation">
            <a href="{{ $compactPrimaryUrl }}" class="{{ ($authUser?->is_admin && request()->routeIs('admin.*')) || ($isCivilian && request()->routeIs('dashboard')) || (! $isCivilian && ! $authUser?->is_admin && request()->routeIs('monitoring')) ? 'active' : '' }}">
                <span>{{ $compactPrimaryCode }}</span>
                <strong>{{ $compactPrimaryLabel }}</strong>
            </a>
            <a href="{{ $compactSecondaryUrl }}" class="{{ ($isCivilian && request()->routeIs('reports.create')) || (! $isCivilian && request()->routeIs('reports.index')) ? 'active' : '' }}">
                <span>{{ $compactSecondaryCode }}</span>
                <strong>{{ $compactSecondaryLabel }}</strong>
            </a>
            <a href="{{ route('profile.show') }}" class="{{ request()->routeIs('profile.*') ? 'active' : '' }}">
                <span>PF</span>
                <strong>Profile</strong>
            </a>
            <a href="{{ route('settings.index') }}" class="{{ request()->routeIs('settings.*') ? 'active' : '' }}">
                <span>ST</span>
                <strong>Settings</strong>
            </a>
        </nav>
    @endif
    @unless ($isCivilian)
        <aside class="alert-center" data-alert-center-panel hidden>
            <div class="alert-top">
                <div>
                    <p class="eyebrow">Alert Center</p>
                    <h3>Latest fatal incidents</h3>
                </div>
                <button type="button" class="pill-btn" data-alert-center-close>Close</button>
            </div>
            <div class="alert-list" data-alert-list>
                @forelse (($fatalAlerts ?? []) as $alert)
                    <a href="{{ route('reports.show', $alert) }}" class="alert-item">
                        <span class="tag red">Fatal</span>
                        <strong>{{ $alert->incident_type }}</strong>
                        <p>{{ $alert->location_text }}</p>
                        <p>{{ optional($alert->created_at)->diffForHumans() }}</p>
                    </a>
                @empty
                    <div class="data-empty">No fatal incidents are queued right now.</div>
                @endforelse
            </div>
        </aside>
    @endunless

    <div class="media-viewer" data-media-viewer hidden>
        <div class="media-viewer-shell">
            <div class="media-viewer-top">
                <div>
                    <p class="eyebrow">Evidence Viewer</p>
                    <strong data-media-viewer-title>Evidence Preview</strong>
                </div>
                <button type="button" class="media-viewer-close" data-media-viewer-close aria-label="Close media viewer">&times;</button>
            </div>
            <div class="media-viewer-frame">
                <img data-media-viewer-image alt="Expanded evidence preview" hidden>
                <video data-media-viewer-video controls playsinline hidden></video>
            </div>
        </div>
    </div>
    <section class="pwa-install-card" data-pwa-install-card hidden>
        <div class="pwa-install-copy">
            <p class="eyebrow">Install Bontoc Rescue</p>
            <strong data-pwa-install-title>Open the emergency system like a mobile app.</strong>
            <p data-pwa-install-copy>Install this web app on your phone or desktop for a full-screen experience, faster launch, and offline fallback screen during weak connectivity.</p>
        </div>
        <div class="pwa-install-actions">
            <button type="button" class="btn btn-primary" data-pwa-install-action>Install App</button>
            <button type="button" class="btn btn-secondary" data-pwa-dismiss-action>Later</button>
        </div>
    </section>
    <section class="pwa-update-card" data-pwa-update-card hidden role="status" aria-live="polite" aria-atomic="true">
        <div class="pwa-update-copy">
            <p class="pwa-update-badge" data-pwa-update-eyebrow>Updating App</p>
            <strong data-pwa-update-title>Applying latest system update.</strong>
            <p data-pwa-update-copy>The website and installed app will reload automatically with the newest emergency workflow and fixes.</p>
        </div>
    </section>
    <script>
        (() => {
            const viewer = document.querySelector('[data-media-viewer]');
            if (!viewer) {
                return;
            }

            const title = viewer.querySelector('[data-media-viewer-title]');
            const image = viewer.querySelector('[data-media-viewer-image]');
            const video = viewer.querySelector('[data-media-viewer-video]');

            const closeViewer = () => {
                viewer.hidden = true;
                document.body.style.overflow = '';
                image.hidden = true;
                image.removeAttribute('src');
                video.pause();
                video.hidden = true;
                video.removeAttribute('src');
                video.load();
            };

            document.addEventListener('click', (event) => {
                const trigger = event.target.closest('[data-media-viewer-trigger]');
                if (trigger) {
                    event.preventDefault();
                    const mediaType = trigger.getAttribute('data-media-type');
                    const mediaSrc = trigger.getAttribute('data-media-src');
                    const mediaTitle = trigger.getAttribute('data-media-title') || 'Evidence Preview';

                    if (!mediaSrc) {
                        return;
                    }

                    title.textContent = mediaTitle;
                    viewer.hidden = false;
                    document.body.style.overflow = 'hidden';

                    if (mediaType === 'video') {
                        image.hidden = true;
                        image.removeAttribute('src');
                        video.src = mediaSrc;
                        video.hidden = false;
                        video.load();
                    } else {
                        video.pause();
                        video.hidden = true;
                        video.removeAttribute('src');
                        video.load();
                        image.src = mediaSrc;
                        image.hidden = false;
                    }

                    return;
                }

                if (event.target.matches('[data-media-viewer-close]') || event.target === viewer) {
                    closeViewer();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && !viewer.hidden) {
                    closeViewer();
                }
            });
        })();
    </script>
</body>
</html>
