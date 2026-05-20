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
        :root{color-scheme:light;--bg:#eef5fb;--surface:#fff;--surface-alt:#f7fbff;--surface-muted:#e7f0f7;--ink:#0f1f2f;--muted:#627181;--line:rgba(15,31,47,.10);--line-strong:rgba(15,31,47,.16);--accent:#c91c21;--accent-strong:#9f1318;--blue:#2068ae;--blue-soft:#dcebff;--green:#1f9d68;--green-soft:#dcf8e9;--amber:#d18b1f;--amber-soft:#fff1d4;--danger:#c72626;--danger-soft:#ffe2e2;--shadow:0 24px 60px rgba(12,25,39,.10);--shadow-soft:0 14px 34px rgba(12,25,39,.06);--radius-xl:30px;--radius-lg:22px;--sidebar-width:248px}
        *{box-sizing:border-box}html{scroll-behavior:smooth}
        body{margin:0;min-height:100vh;color:var(--ink);font-family:"Trebuchet MS","Aptos",sans-serif;background:radial-gradient(circle at top left,rgba(255,255,255,.95),transparent 26%),radial-gradient(circle at 88% 12%,rgba(201,28,33,.10),transparent 24%),linear-gradient(180deg,#f8fbff 0%,#edf4fb 34%,#e5edf4 100%)}
        a{color:inherit;text-decoration:none}button,input,select,textarea{font:inherit}
        .ops-shell{display:grid;grid-template-columns:var(--sidebar-width) minmax(0,1fr);min-height:100vh}.ops-sidebar{position:sticky;top:0;height:100vh;padding:24px 18px 20px;background:rgba(255,255,255,.74);border-right:1px solid var(--line);backdrop-filter:blur(18px);display:grid;grid-template-rows:auto auto minmax(0,1fr) auto;gap:22px;overflow-y:auto;overflow-x:hidden;overscroll-behavior:contain;-webkit-overflow-scrolling:touch;scrollbar-gutter:stable}.ops-main{padding:20px;display:grid;gap:18px;min-width:0;position:relative}
        .brand-lockup,.sidebar-cluster,.sidebar-foot,.stack,.incident-stack,.timeline,.alert-list,.content-stack,.panel-heading,.hero-copy{display:grid;gap:12px}.brand-lockup span,.topbar-label,.sidebar-label,.eyebrow,.panel-kicker,.metric-card span,.system-card span,.field label,.form-note{font-size:.74rem;font-weight:800;letter-spacing:.16em;text-transform:uppercase}
        .brand-lockup span{display:inline-flex;align-items:center;gap:8px;color:var(--accent)}.brand-lockup span::before{content:"";width:10px;height:10px;border-radius:999px;background:var(--accent);box-shadow:0 0 0 6px rgba(201,28,33,.10)}
        .brand-lockup strong,.topbar-copy h1,.hero-copy h2,.panel-title,.incident-title,.alert-top h3,.media-viewer-top strong,.metric-card strong{font-family:"Bahnschrift","Trebuchet MS",sans-serif}
        .brand-lockup strong{font-size:1.32rem;line-height:1.2;letter-spacing:-.03em}.brand-lockup p,.hero-copy p,.topbar-copy span,.metric-card p,.panel p,.preview-card p,.timeline-copy p,.detail-copy,.incident-summary,.system-card p,.alert-item p,.input,.textarea,select,.muted{color:var(--muted)}
        .sidebar-label strong{color:var(--ink)}.sidebar-nav{display:grid;gap:8px}.sidebar-nav a{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 16px;border-radius:18px;border:1px solid transparent;color:var(--muted);font-weight:700;background:transparent;transition:transform .2s ease,border-color .2s ease,background .2s ease,color .2s ease}.sidebar-nav a:hover,.sidebar-nav a:focus-visible{transform:translateX(2px);background:rgba(255,255,255,.76);border-color:var(--line);color:var(--ink)}.sidebar-nav a.active{background:#fff;border-color:rgba(201,28,33,.18);color:var(--accent);box-shadow:var(--shadow-soft)}.sidebar-nav small{display:inline-flex;min-width:28px;min-height:28px;align-items:center;justify-content:center;border-radius:999px;background:rgba(15,31,47,.06);font-size:.72rem;font-weight:800}.sidebar-nav a.active small{background:rgba(201,28,33,.12);color:var(--accent)}
        .sidebar-status-card{padding:18px;border-radius:22px;background:linear-gradient(180deg,rgba(15,31,47,.98),rgba(21,47,68,.92));color:#f7fbff;box-shadow:var(--shadow-soft);display:grid;gap:10px;overflow:hidden;position:relative}.sidebar-status-card::after{content:"";position:absolute;inset:auto -20% -30% auto;width:160px;height:160px;background:radial-gradient(circle,rgba(255,255,255,.12),transparent 62%)}.status-chip{display:inline-flex;align-items:center;gap:8px;font-size:.78rem;font-weight:800;letter-spacing:.14em;text-transform:uppercase}.status-chip::before{content:"";width:9px;height:9px;border-radius:999px;background:#38d39f;box-shadow:0 0 0 5px rgba(56,211,159,.16)}.sidebar-logout{display:grid}.sidebar-logout button{width:100%;justify-content:space-between}
        .topbar,.hero-card,.panel,.metric-card,.system-card,.flash{background:rgba(255,255,255,.78);border:1px solid var(--line);border-radius:var(--radius-xl);box-shadow:var(--shadow-soft);backdrop-filter:blur(18px)}.topbar{padding:18px 20px;display:flex;align-items:center;justify-content:space-between;gap:18px}.topbar-copy{display:grid;gap:8px;min-width:0}.topbar-copy p,.topbar-copy h1,.topbar-copy span,.alert-top p,.alert-top h3{margin:0}.topbar-label,.eyebrow{color:var(--accent)}.topbar-copy h1{font-size:clamp(2rem,3vw,3.4rem);line-height:.95;letter-spacing:-.05em}.topbar-actions,.hero-actions,.button-row,.tag-row,.panel-actions,.preview-grid,.action-row{display:flex;flex-wrap:wrap;gap:12px;align-items:center}
        .pill-btn,.mini-chip,.action-btn,.btn{display:inline-flex;align-items:center;justify-content:center;gap:10px;min-height:46px;padding:0 18px;border-radius:999px;border:1px solid transparent;cursor:pointer;transition:transform .2s ease,box-shadow .2s ease,border-color .2s ease,background .2s ease}.pill-btn:hover,.action-btn:hover,.btn:hover{transform:translateY(-1px)}.pill-btn{font-weight:800;color:var(--ink);background:#fff;border-color:var(--line)}.pill-btn strong{font-size:.82rem;color:var(--accent)}.mini-chip{min-height:40px;padding:0 14px;background:var(--surface-alt);border-color:var(--line);color:var(--muted);font-size:.82rem;font-weight:700}
        .identity-card{display:inline-flex;align-items:center;gap:12px;padding:8px 10px 8px 8px;border-radius:999px;background:#fff;border:1px solid var(--line)}.identity-card strong,.identity-card span{display:block}.identity-card strong{font-size:.9rem}.identity-card span{font-size:.76rem;color:var(--muted);font-weight:700;letter-spacing:.10em;text-transform:uppercase}.avatar{width:44px;height:44px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#153c5e,#1f6ca8);color:#fff;font-size:.9rem;font-weight:900;letter-spacing:.08em;overflow:hidden}.avatar img{width:100%;height:100%;object-fit:cover;display:block}
        .system-strip{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:12px}.system-card{padding:16px 18px;display:grid;gap:8px}.system-card strong{font-size:.95rem;line-height:1.4}.system-card p{margin:0;font-size:.84rem}.system-dot{display:inline-flex;align-items:center;gap:8px}.system-dot::before{content:"";width:10px;height:10px;border-radius:999px;background:var(--green);box-shadow:0 0 0 6px rgba(31,157,104,.12)}.system-dot.warning::before{background:var(--amber);box-shadow:0 0 0 6px rgba(209,139,31,.14)}
        .hero-card{padding:24px;display:grid;gap:20px}.hero-grid{display:grid;grid-template-columns:minmax(0,1.14fr) minmax(280px,.86fr);gap:20px;align-items:start}.responder-lean-hero .hero-grid{grid-template-columns:minmax(0,1fr)}.responder-lean-hero .hero-copy{max-width:920px}.hero-copy h2{margin:0;font-size:clamp(2rem,2.8vw,3rem);line-height:1.02;letter-spacing:-.05em}.hero-copy p,.panel p{margin:0;line-height:1.75}.btn,.action-btn{font-weight:800}.btn-primary,.action-btn.primary{background:linear-gradient(135deg,var(--accent),var(--accent-strong));color:#fff;box-shadow:0 18px 30px rgba(201,28,33,.22)}.btn-secondary,.action-btn.secondary{background:#fff;border-color:rgba(32,104,174,.16);color:var(--blue)}.btn-danger,.action-btn.danger{background:#fff;border-color:rgba(199,38,38,.20);color:var(--danger)}
        .hero-metrics,.metric-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.metric-card{padding:18px;display:grid;gap:10px;min-height:140px}.metric-card strong{font-size:2.25rem;line-height:1;letter-spacing:-.06em}.flash{padding:16px 18px;color:var(--blue);font-weight:700}.flash.error{color:var(--danger)}.panel{padding:20px;min-width:0}.panel-head{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;margin-bottom:16px}.panel-title{margin:0;font-size:1.45rem;line-height:1.08;letter-spacing:-.04em}.section-copy{margin:0;line-height:1.7;color:var(--muted)}
        .dual-grid{display:grid;grid-template-columns:minmax(0,1.08fr) minmax(280px,.92fr);gap:18px}.triple-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}.view-switch{display:inline-flex;align-items:center;gap:6px;padding:6px;border-radius:999px;background:var(--surface-alt);border:1px solid var(--line)}.view-switch button{border:0;background:transparent;color:var(--muted);font-size:.82rem;font-weight:800;letter-spacing:.10em;text-transform:uppercase;padding:12px 14px;border-radius:999px;cursor:pointer}.view-switch button.is-active{background:#fff;color:var(--ink);box-shadow:var(--shadow-soft)}.monitor-shell{display:grid;grid-template-columns:minmax(0,1.15fr) minmax(290px,.85fr);gap:18px}.monitor-shell[data-view-mode="list"]{grid-template-columns:1fr}.monitor-shell[data-view-mode="list"] [data-view-panel="map"]{display:none}.monitor-shell[data-view-mode="map"]{grid-template-columns:1fr}.monitor-shell[data-view-mode="map"] [data-view-panel="list"]{display:none}
        .summary-strip{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px}.summary-card{padding:16px;border-radius:18px;background:#fff;border:1px solid rgba(15,31,47,.08);display:grid;gap:8px;min-width:0;box-shadow:none}.summary-card strong{font-size:1.35rem;line-height:1;letter-spacing:-.04em}.summary-card p{margin:0;font-size:.9rem;line-height:1.55}.workspace-grid{display:grid;grid-template-columns:minmax(0,1.16fr) minmax(280px,.84fr);gap:18px}.workspace-side,.meta-list,.settings-grid,.toggle-stack{display:grid;gap:14px}.panel-note{padding:16px 18px;border-radius:16px;background:#fff;border:1px solid rgba(15,31,47,.08);min-width:0}.meta-row{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;padding:12px 0;border-bottom:1px solid rgba(15,31,47,.08)}.meta-row:last-child{border-bottom:0;padding-bottom:0}.meta-row span{color:var(--muted);font-size:.9rem;line-height:1.55}.meta-row strong{display:block;font-size:.95rem;line-height:1.45;color:var(--ink)}.subtle{font-size:.88rem;color:var(--muted)}.panel-toolbar{display:flex;flex-wrap:wrap;gap:12px;align-items:center;justify-content:space-between}.toolbar-actions{display:flex;flex-wrap:wrap;gap:12px;align-items:center}.settings-grid{grid-template-columns:repeat(auto-fit,minmax(220px,1fr))}.setting-card{padding:16px;border-radius:18px;background:#fff;border:1px solid rgba(15,31,47,.08);display:grid;gap:10px;min-width:0;box-shadow:none}.setting-card h3,.setting-card strong{margin:0;font-size:1rem;line-height:1.4}.toggle-item{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;padding:14px 16px;border-radius:16px;background:#fff;border:1px solid rgba(15,31,47,.08)}.toggle-copy{display:grid;gap:6px;min-width:0}.toggle-copy p{margin:0;font-size:.88rem;line-height:1.55}.switch{position:relative;display:inline-flex;align-items:center;min-width:52px;height:32px}.switch input{position:absolute;opacity:0;inset:0;cursor:pointer}.switch-track{width:52px;height:32px;border-radius:999px;background:rgba(15,31,47,.14);position:relative;transition:background .2s ease}.switch-track::after{content:"";position:absolute;top:4px;left:4px;width:24px;height:24px;border-radius:50%;background:#fff;box-shadow:0 4px 12px rgba(15,31,47,.18);transition:transform .2s ease}input:checked + .switch-track{background:linear-gradient(135deg,var(--accent),var(--accent-strong))}input:checked + .switch-track::after{transform:translateX(20px)}
        .incident-card{display:grid;grid-template-columns:8px minmax(148px,.24fr) minmax(0,1fr);gap:16px;padding:18px;border-radius:24px;background:#fff;border:1px solid rgba(15,31,47,.08);box-shadow:var(--shadow-soft);min-width:0}.incident-card.compact-live-card{grid-template-columns:8px minmax(128px,.22fr) minmax(0,1fr)}.incident-rail{border-radius:999px;min-height:100%;background:var(--blue)}.incident-rail.severity-Fatal{background:var(--danger)}.incident-rail.severity-Serious{background:var(--amber)}.incident-rail.severity-Minor{background:var(--green)}.incident-meta{display:grid;align-content:start;gap:10px;min-width:0}.incident-code{font-size:.72rem;font-weight:800;letter-spacing:.16em;text-transform:uppercase;color:var(--muted);overflow-wrap:anywhere}.incident-time,.incident-priority{font-size:1.1rem;font-weight:900;line-height:1.2;letter-spacing:-.03em}.tag,.badge,.severity-chip{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:7px 12px;border-radius:999px;font-size:.72rem;font-weight:900;letter-spacing:.14em;text-transform:uppercase;max-width:100%}.tag.neutral,.badge.neutral{background:var(--surface-muted);color:var(--ink)}.tag.blue,.badge.blue{background:var(--blue-soft);color:var(--blue)}.tag.green,.badge.green,.badge.success{background:var(--green-soft);color:var(--green)}.tag.amber,.badge.amber{background:var(--amber-soft);color:var(--amber)}.tag.red,.badge.red,.badge.critical{background:var(--danger-soft);color:var(--danger)}
        .incident-content{display:grid;gap:14px;min-width:0}.incident-headline{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;flex-wrap:wrap}.incident-title{margin:0;font-size:1.55rem;line-height:1.08;letter-spacing:-.04em;overflow-wrap:anywhere}.detail-copy{font-size:.96rem;line-height:1.75}.detail-copy strong{color:var(--ink)}.incident-summary{margin:0;font-size:.96rem;line-height:1.75;overflow-wrap:anywhere}.preview-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:14px}.preview-card,.detail-card{padding:16px;border-radius:20px;background:var(--surface-alt);border:1px solid rgba(15,31,47,.08);display:grid;gap:12px;min-width:0}.preview-card strong,.detail-card strong{font-size:.96rem}.preview-card p,.detail-card p,.panel-note p,.summary-card p,.setting-card p,.toggle-copy p,.meta-row span{overflow-wrap:anywhere}.preview-card img,.preview-card video,.preview-thumb,.detail-media{display:block;width:100%;border-radius:18px;background:#102332;object-fit:cover}.preview-thumb{max-height:172px}.detail-media{max-height:420px}
        .media-trigger{padding:0;border:0;background:transparent;cursor:zoom-in;text-align:left;color:inherit;display:grid;gap:10px}.media-trigger span{font-size:.78rem;font-weight:800;color:var(--blue)}.form-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px}.field{display:grid;gap:8px;min-width:0}.field label{font-size:.82rem;font-weight:800;letter-spacing:.10em;text-transform:uppercase}.input,.textarea,select{width:100%;min-height:50px;padding:14px 16px;border-radius:18px;border:1px solid var(--line-strong);background:#fff;min-width:0}.textarea{min-height:140px;resize:vertical}.divider{height:1px;background:var(--line);margin:4px 0}.incident-actions{display:grid;gap:14px}.action-row form{display:contents}
        .map-shell{overflow:hidden}.map-canvas{width:100%;min-height:420px;border-radius:24px;overflow:hidden;border:1px solid rgba(15,31,47,.08)}.leaflet-container{font:inherit}.timeline-item{display:grid;grid-template-columns:16px minmax(0,1fr);gap:14px;align-items:start}.timeline-marker{width:16px;height:16px;border-radius:999px;background:var(--blue);box-shadow:0 0 0 8px rgba(32,104,174,.12);margin-top:6px}.timeline-copy{padding:16px 18px;border-radius:18px;background:var(--surface-alt);border:1px solid rgba(15,31,47,.08);display:grid;gap:8px}.timeline-copy .meta{font-size:.78rem;color:var(--muted);font-weight:700;letter-spacing:.08em;text-transform:uppercase}
        .alert-center{position:fixed;top:92px;right:26px;width:min(360px,calc(100vw - 30px));max-height:calc(100vh - 120px);padding:18px;border-radius:28px;background:rgba(255,255,255,.94);border:1px solid var(--line);box-shadow:var(--shadow);backdrop-filter:blur(20px);z-index:1100;display:grid;gap:16px;overflow:auto}.alert-center[hidden]{display:none}.alert-top{display:flex;align-items:flex-start;justify-content:space-between;gap:16px}.alert-top h3{font-size:1.3rem;line-height:1.05;letter-spacing:-.04em}.alert-item{padding:14px 16px;border-radius:18px;background:var(--surface-alt);border:1px solid rgba(15,31,47,.08);display:grid;gap:8px}.alert-item strong{font-size:.98rem;line-height:1.35}.alert-item p{margin:0;font-size:.88rem;line-height:1.65}.data-empty{padding:20px;border-radius:22px;background:var(--surface-alt);border:1px dashed rgba(15,31,47,.16);color:var(--muted)}
        .live-toast-stack{position:fixed;top:24px;right:24px;display:grid;gap:10px;width:min(360px,calc(100vw - 30px));z-index:1300}.live-toast{background:rgba(15,31,47,.95);color:#fff;padding:16px 18px;border-radius:18px;box-shadow:var(--shadow);display:grid;gap:6px}.live-toast strong{font-size:.96rem}.media-viewer[hidden]{display:none}.media-viewer{position:fixed;inset:0;z-index:1500;background:rgba(15,31,47,.84);backdrop-filter:blur(14px);padding:24px}.media-viewer-shell{width:min(1120px,100%);max-height:calc(100vh - 48px);margin:0 auto;padding:22px;border-radius:28px;background:rgba(255,255,255,.98);box-shadow:var(--shadow);display:grid;gap:16px}.media-viewer-top{display:flex;align-items:flex-start;justify-content:space-between;gap:14px}.media-viewer-top strong{display:block;font-size:1.2rem;line-height:1.25;letter-spacing:-.03em}.media-viewer-close{width:46px;height:46px;border:0;border-radius:999px;background:rgba(15,31,47,.08);cursor:pointer;font-size:1.5rem}.media-viewer-frame{min-height:320px;max-height:calc(100vh - 180px);display:flex;align-items:center;justify-content:center;border-radius:24px;overflow:hidden;background:#102332}.media-viewer-frame img,.media-viewer-frame video{width:100%;max-height:calc(100vh - 200px);object-fit:contain;background:#102332}.sidebar-toggle,.sidebar-close,.sidebar-mobile-head{display:none}.sidebar-backdrop{display:none}
        .pwa-shell-pill{display:inline-flex;align-items:center;gap:10px;padding:11px 14px;border-radius:999px;background:rgba(255,255,255,.9);border:1px solid var(--line);font-size:.78rem;font-weight:900;letter-spacing:.10em;text-transform:uppercase;box-shadow:var(--shadow-soft)}.pwa-shell-pill strong{font-size:.78rem;color:var(--ink)}.pwa-shell-pill::before{content:"";width:10px;height:10px;border-radius:999px;background:var(--green);box-shadow:0 0 0 6px rgba(31,157,104,.12)}.pwa-shell-pill.is-offline::before{background:var(--amber);box-shadow:0 0 0 6px rgba(209,139,31,.14)}
        .pwa-install-card,.pwa-update-card{position:fixed;z-index:1350;display:grid;gap:14px;align-items:center;padding:18px 20px;border-radius:26px;background:rgba(15,31,47,.95);color:#fff;box-shadow:var(--shadow);backdrop-filter:blur(18px)}.pwa-install-card{left:24px;right:24px;bottom:24px;grid-template-columns:minmax(0,1fr) auto;max-width:880px;margin:0 auto}.pwa-update-card{top:24px;right:24px;left:auto;bottom:auto;width:min(340px,calc(100vw - 30px));padding:14px 16px;grid-template-columns:1fr;border-radius:22px;background:rgba(15,31,47,.92)}.pwa-install-card[hidden],.pwa-update-card[hidden]{display:none}.pwa-install-copy,.pwa-update-copy{display:grid;gap:8px}.pwa-install-copy p,.pwa-install-copy strong,.pwa-update-copy p,.pwa-update-copy strong{margin:0}.pwa-install-copy strong,.pwa-update-copy strong{font-size:1rem;letter-spacing:-.02em}.pwa-update-copy strong{font-size:.96rem}.pwa-install-copy p,.pwa-update-copy p{font-size:.92rem;line-height:1.65;color:rgba(255,255,255,.78)}.pwa-update-copy p{font-size:.82rem;line-height:1.5}.pwa-install-actions{display:flex;flex-wrap:wrap;gap:12px;align-items:center;justify-content:flex-end}.pwa-install-card .btn-secondary,.pwa-update-card .btn-secondary{background:rgba(255,255,255,.1);border-color:rgba(255,255,255,.18);color:#fff}.pwa-install-card .btn-primary,.pwa-update-card .btn-primary{box-shadow:none}.pwa-update-badge{display:inline-flex;align-items:center;gap:8px;font-size:.68rem;font-weight:900;letter-spacing:.12em;text-transform:uppercase;color:#fff}.pwa-update-badge::before{content:"";width:9px;height:9px;border-radius:999px;background:#38d39f;box-shadow:0 0 0 5px rgba(56,211,159,.15);animation:pwaPulse 1.2s ease-in-out infinite}
        @keyframes pwaPulse{0%,100%{transform:scale(1);opacity:1}50%{transform:scale(.78);opacity:.75}}
        @media (max-width:1520px){.hero-grid,.dual-grid,.monitor-shell,.triple-grid,.workspace-grid,.civilian-home-grid,.report-form-shell,.profile-shell-grid{grid-template-columns:1fr}.monitor-shell[data-view-mode="split"]{grid-template-columns:1fr}.incident-card,.incident-card.compact-live-card{grid-template-columns:8px 1fr}.incident-meta,.incident-content{grid-column:2}.civilian-account-summary-grid,.civilian-account-form-grid,.civilian-account-action-grid,.profile-photo-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.panel-toolbar,.panel-head,.topbar-actions{align-items:flex-start}.view-switch{width:100%;justify-content:flex-start;flex-wrap:wrap}.map-canvas{min-height:360px}}
        @media (max-width:1380px){.system-strip{grid-template-columns:repeat(3,minmax(0,1fr))}.ops-shell{display:block}.ops-sidebar{position:fixed;left:0;top:0;bottom:0;width:min(88vw,340px);height:100vh;padding:24px 18px 20px;transform:translateX(-105%);transition:transform .25s ease;z-index:1450;overflow-y:auto;overflow-x:hidden;background:rgba(255,255,255,.96);border-right:1px solid var(--line);box-shadow:var(--shadow)}.ops-sidebar.is-open{transform:translateX(0)}.ops-main{padding:18px}.sidebar-toggle,.sidebar-close{display:inline-flex}.sidebar-mobile-head{display:flex;align-items:center;justify-content:space-between;gap:12px}.sidebar-backdrop{display:block;position:fixed;inset:0;background:rgba(15,31,47,.36);backdrop-filter:blur(4px);border:0;z-index:1400}.sidebar-backdrop[hidden]{display:none}body.sidebar-open{overflow:hidden}}
        @media (max-width:980px){.topbar,.hero-card,.panel,.metric-card,.system-card{border-radius:24px}.topbar{flex-direction:column;align-items:stretch}.topbar-actions{justify-content:flex-start}.system-strip{grid-template-columns:repeat(2,minmax(0,1fr))}.hero-metrics,.metric-grid,.preview-grid,.summary-strip,.settings-grid,.civilian-action-grid,.capture-action-grid,.report-requirements-grid,.civilian-report-stats,.civilian-account-summary-grid,.civilian-account-form-grid,.civilian-account-action-grid,.profile-photo-grid{grid-template-columns:1fr}.action-row,.hero-actions,.button-row,.panel-actions,.toolbar-actions,.pwa-install-actions,.pwa-update-actions,.civilian-history-actions{flex-direction:column;align-items:stretch}.pill-btn,.action-btn,.btn,.mini-chip{width:100%}.alert-center{top:18px;right:18px;left:18px;width:auto}.brand-lockup p{display:none}.meta-row,.toggle-item,.civilian-history-top{flex-direction:column;align-items:stretch}.pwa-install-card{left:18px;right:18px;grid-template-columns:1fr}.pwa-update-card{top:18px;right:18px;left:auto;bottom:auto;grid-template-columns:1fr;width:min(320px,calc(100vw - 36px))}.pwa-install-card{bottom:18px}.report-submit-card{position:static}.map-canvas{min-height:320px}}
        @media (max-width:620px){.ops-main{padding:14px}.system-strip{grid-template-columns:1fr}.view-switch{width:100%;justify-content:space-between}.view-switch button{flex:1}.identity-card{width:100%;justify-content:space-between}.media-viewer{padding:12px}.media-viewer-shell{padding:16px;border-radius:22px}.ops-sidebar{width:min(92vw,320px);padding:20px 14px 18px}.pwa-install-card{left:12px;right:12px;padding:16px}.pwa-update-card{top:12px;right:12px;left:auto;padding:14px;width:min(300px,calc(100vw - 24px))}.pwa-install-card{bottom:12px}.civilian-hero-callout,.civilian-helper-card,.civilian-action-card,.civilian-history-card,.form-step-card,.requirement-item{padding:16px}}
    </style>
    <style>
        .civilian-shell{display:grid;gap:20px}
        .civilian-home-grid{display:grid;grid-template-columns:minmax(0,1.06fr) minmax(280px,.94fr);gap:18px}
        .civilian-action-grid,.capture-action-grid,.report-requirements-grid,.civilian-report-stats{display:grid;gap:14px;grid-template-columns:repeat(2,minmax(0,1fr))}
        .civilian-hero-callout,.civilian-helper-card,.civilian-action-card,.civilian-history-card,.form-step-card,.requirement-item{padding:18px;border-radius:20px;border:1px solid rgba(15,31,47,.08);background:#fff;display:grid;gap:12px}
        .civilian-hero-callout{background:linear-gradient(180deg,#fff,rgba(247,251,255,.98));color:var(--ink);border-color:rgba(32,104,174,.12)}
        .civilian-hero-callout p,.civilian-hero-callout li{color:var(--muted)}
        .civilian-hero-callout strong,.civilian-hero-callout h3,.civilian-action-card strong,.civilian-helper-card strong,.civilian-history-card strong,.form-step-card strong,.requirement-item strong{margin:0;font-size:1rem;line-height:1.4}
        .civilian-hero-callout h3,.civilian-history-title{margin:0;font-family:"Bahnschrift","Trebuchet MS",sans-serif;font-size:1.4rem;line-height:1.1;letter-spacing:-.03em}
        .civilian-hero-callout ul,.civilian-checklist{margin:0;padding:0;list-style:none;display:grid;gap:10px}
        .civilian-hero-callout li,.civilian-check-item{display:flex;gap:10px;align-items:flex-start}
        .civilian-hero-callout li::before,.civilian-check-item::before{content:"";width:10px;height:10px;margin-top:7px;border-radius:999px;background:var(--green);box-shadow:0 0 0 6px rgba(31,157,104,.12);flex:0 0 auto}
        .civilian-action-card{background:linear-gradient(135deg,rgba(255,255,255,.98),rgba(235,244,255,.96));align-content:start}
        .civilian-action-card.primary{background:linear-gradient(135deg,var(--accent),var(--accent-strong));border-color:rgba(201,28,33,.28);box-shadow:0 22px 44px rgba(201,28,33,.18)}
        .civilian-action-card.primary strong,.civilian-action-card.primary p,.civilian-action-card.primary .civilian-kicker{color:#fff}
        .civilian-action-card.primary p{color:rgba(255,255,255,.84)}
        .civilian-kicker,.form-step-label{display:inline-flex;align-items:center;justify-content:center;min-height:30px;padding:0 12px;border-radius:999px;background:rgba(15,31,47,.06);font-size:.72rem;font-weight:900;letter-spacing:.14em;text-transform:uppercase;color:var(--ink);width:max-content}
        .civilian-action-card.primary .civilian-kicker{background:rgba(255,255,255,.16)}
        .civilian-helper-card{background:linear-gradient(135deg,rgba(255,255,255,.98),rgba(241,247,253,.96))}
        .civilian-pill-list{display:flex;flex-wrap:wrap;gap:10px}
        .civilian-pill{display:inline-flex;align-items:center;gap:10px;padding:10px 14px;border-radius:999px;background:#fff;border:1px solid rgba(15,31,47,.08);font-size:.82rem;font-weight:800;color:var(--ink)}
        .civilian-pill::before{content:"";width:10px;height:10px;border-radius:999px;background:var(--blue);box-shadow:0 0 0 6px rgba(32,104,174,.12)}
        .civilian-history-stack{display:grid;gap:16px}
        .civilian-history-card{background:#fff;box-shadow:var(--shadow-soft)}
        .civilian-history-top{display:flex;justify-content:space-between;align-items:flex-start;gap:14px;flex-wrap:wrap}
        .civilian-history-meta{display:grid;gap:10px}
        .civilian-history-meta p,.civilian-helper-card p,.civilian-action-card p,.form-step-card p,.requirement-item p{margin:0;line-height:1.65;color:var(--muted)}
        .civilian-report-stats{grid-template-columns:repeat(auto-fit,minmax(150px,1fr))}
        .civilian-stat-card{padding:14px 16px;border-radius:18px;background:var(--surface-alt);border:1px solid rgba(15,31,47,.08);display:grid;gap:8px}
        .civilian-stat-card span{font-size:.72rem;font-weight:800;letter-spacing:.14em;text-transform:uppercase;color:var(--muted)}
        .civilian-stat-card strong{font-size:.94rem;line-height:1.4}
        .civilian-progress-block{display:grid;gap:10px}
        .civilian-progress-track{height:12px;border-radius:999px;background:rgba(15,31,47,.08);overflow:hidden}
        .civilian-progress-fill{height:100%;border-radius:999px;background:linear-gradient(135deg,var(--blue),#7ab8ef)}
        .civilian-progress-fill.is-complete{background:linear-gradient(135deg,var(--green),#6dd4a4)}
        .civilian-progress-fill.is-rejected{background:linear-gradient(135deg,var(--danger),#ea6c6c)}
        .civilian-history-actions{display:flex;flex-wrap:wrap;gap:10px}
        .civilian-empty-state{padding:22px;border-radius:24px;border:1px dashed rgba(15,31,47,.16);background:rgba(255,255,255,.84);display:grid;gap:10px}
        .civilian-simple-hero{background:rgba(255,255,255,.96);border-color:rgba(15,31,47,.10)}
        .civilian-simple-hero-grid,.civilian-report-hero-grid{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:18px;align-items:center}
        .civilian-simple-copy h2,.civilian-report-hero h2{font-size:clamp(1.8rem,3vw,2.45rem)}
        .civilian-simple-stats{display:grid;grid-template-columns:repeat(3,minmax(92px,1fr));gap:10px}
        .civilian-simple-stats article,.civilian-report-quick-list span,.civilian-home-status-row article{padding:12px 14px;border-radius:16px;background:#fff;border:1px solid rgba(15,31,47,.10);box-shadow:none;display:grid;gap:6px}
        .civilian-simple-stats span,.civilian-home-status-row span{font-size:.7rem;font-weight:900;letter-spacing:.14em;text-transform:uppercase;color:var(--muted)}
        .civilian-simple-stats strong,.civilian-home-status-row strong{font-size:1.8rem;line-height:1;font-family:"Bahnschrift","Trebuchet MS",sans-serif;letter-spacing:-.05em}
        .civilian-home-compact{max-width:920px;margin:0 auto;width:100%}
        .civilian-home-card{padding:20px;border-radius:20px;background:#fff;color:var(--ink);border:1px solid rgba(15,31,47,.10);box-shadow:var(--shadow-soft);display:grid;gap:16px;overflow:hidden;position:relative}
        .civilian-home-card::after{display:none}
        .civilian-home-card-copy{display:grid;gap:8px}
        .civilian-home-card-copy h2{margin:0;font-family:"Bahnschrift","Trebuchet MS",sans-serif;font-size:clamp(1.55rem,4.5vw,2.25rem);line-height:1.05;letter-spacing:-.04em}
        .civilian-home-card-copy p{color:var(--muted)}
        .civilian-home-card .panel-kicker{color:var(--accent)}
        .civilian-home-primary-actions{display:grid;grid-template-columns:1.2fr .8fr;gap:12px}
        .civilian-home-card .btn-secondary{background:#fff;border-color:rgba(32,104,174,.18);color:var(--blue)}
        .civilian-home-mini-actions{display:flex;gap:10px;flex-wrap:wrap}
        .civilian-home-mini-actions a{display:inline-flex;align-items:center;justify-content:center;min-height:40px;padding:0 14px;border-radius:999px;background:var(--surface-alt);border:1px solid rgba(15,31,47,.08);color:var(--ink);font-weight:800}
        .civilian-home-status-row{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}
        .civilian-latest-panel{padding:18px}
        .civilian-latest-card{padding:16px;border-radius:18px;border:1px solid rgba(15,31,47,.10);background:#fff;box-shadow:none;display:grid;grid-template-columns:minmax(0,1fr) auto auto;gap:14px;align-items:center}
        .civilian-latest-card h3{margin:4px 0 2px;font-family:"Bahnschrift","Trebuchet MS",sans-serif;font-size:1.35rem;line-height:1.1;letter-spacing:-.03em}
        .civilian-latest-card p{margin:0}
        .civilian-success-hero{background:rgba(255,255,255,.97);border-color:rgba(15,31,47,.10);overflow:hidden}
        .civilian-success-layout{display:grid;grid-template-columns:minmax(0,1fr) minmax(260px,.88fr);gap:16px;align-items:start}
        .civilian-success-cards{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;min-width:0}
        .civilian-success-card{padding:14px 16px;border-radius:16px;background:var(--surface-alt);border:1px solid rgba(15,31,47,.08);display:grid;gap:8px;min-width:0}
        .civilian-success-card span{font-size:.68rem;font-weight:900;letter-spacing:.14em;text-transform:uppercase;color:var(--muted)}
        .civilian-success-card strong{font-family:"Bahnschrift","Trebuchet MS",sans-serif;font-size:clamp(1.1rem,3vw,1.75rem);line-height:1.08;letter-spacing:-.04em;overflow-wrap:anywhere;word-break:break-word}
        .civilian-success-flow{display:grid;grid-template-columns:minmax(0,1fr) minmax(260px,.78fr);gap:14px;max-width:920px;margin:0 auto;width:100%}
        .civilian-report-hero{background:#fff;border-color:rgba(15,31,47,.10)}
        .civilian-report-quick-list{display:grid;grid-template-columns:repeat(2,minmax(94px,1fr));gap:10px}
        .civilian-report-quick-list span{min-height:58px;align-content:center;text-align:center;font-size:.78rem;font-weight:900;letter-spacing:.12em;text-transform:uppercase;color:var(--ink)}
        .civilian-report-mobile-flow{display:grid;gap:14px;max-width:820px;margin:0 auto;width:100%}
        .civilian-capture-panel{padding:18px;border-radius:20px;background:#fff;box-shadow:none}
        .civilian-mobile-hint{font-size:.95rem}
        .civilian-four-button-grid{grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}
        .civilian-three-button-grid{grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}
        .civilian-four-button-grid .capture-action-card,.civilian-three-button-grid .capture-action-card{min-height:140px;align-content:space-between;border-radius:18px;padding:14px}
        .capture-action-icon{display:inline-flex;width:38px;height:38px;border-radius:12px;align-items:center;justify-content:center;background:rgba(255,255,255,.18);color:#fff;font-weight:900}
        .civilian-mobile-status-card{padding:12px 14px;border-radius:16px;background:var(--surface-alt);border:1px solid rgba(15,31,47,.08);display:grid;gap:6px}
        .civilian-mobile-status-card p{font-weight:800;color:var(--ink);line-height:1.5}
        .civilian-gps-fallback-panel[hidden]{display:none}
        .civilian-gps-fallback-panel{padding:14px;border-radius:18px;background:linear-gradient(180deg,#fff,rgba(255,248,232,.96));border:1px solid rgba(209,139,31,.24);display:grid;gap:10px}
        .civilian-gps-fallback-panel strong{font-size:1rem;line-height:1.35}
        .civilian-gps-fallback-panel p{margin:0;line-height:1.55;color:var(--muted)}
        .civilian-compact-details{border:1px solid rgba(15,31,47,.10);border-radius:18px;background:#fff;overflow:hidden}
        .civilian-compact-details summary{padding:14px 16px;cursor:pointer;font-weight:900;color:var(--blue)}
        .civilian-compact-details .preview-grid{padding:0 16px 16px}
        .civilian-description-panel,.civilian-send-card{border-radius:20px}
        .civilian-description-panel .textarea{min-height:118px}
        .civilian-send-card{position:static;background:linear-gradient(180deg,#fff,rgba(255,248,248,.98));color:var(--ink);border-color:rgba(201,28,33,.16)}
        .civilian-send-card p{color:var(--muted)}
        .civilian-send-card .btn{width:100%}
        .visually-hidden-control{position:absolute!important;width:1px!important;height:1px!important;padding:0!important;margin:-1px!important;overflow:hidden!important;clip:rect(0,0,0,0)!important;white-space:nowrap!important;border:0!important}
        .selfie-camera-modal[hidden]{display:none}
        .selfie-camera-modal{position:fixed;inset:0;z-index:1700;display:flex;align-items:center;justify-content:center;padding:18px;background:rgba(15,31,47,.82);backdrop-filter:blur(16px)}
        .selfie-camera-sheet{width:min(520px,100%);max-height:calc(100vh - 36px);overflow:auto;padding:18px;border-radius:28px;background:#fff;border:1px solid rgba(255,255,255,.24);box-shadow:var(--shadow);display:grid;gap:14px}
        .selfie-camera-copy{display:grid;gap:8px}
        .selfie-camera-copy h3{margin:0;font-family:"Bahnschrift","Trebuchet MS",sans-serif;font-size:1.55rem;line-height:1.08;letter-spacing:-.04em}
        .selfie-camera-copy p{margin:0;color:var(--muted);line-height:1.55}
        .selfie-camera-frame{position:relative;overflow:hidden;border-radius:24px;background:#071523;border:1px solid rgba(15,31,47,.12);aspect-ratio:3/4}
        .selfie-camera-frame video{display:block;width:100%;height:100%;object-fit:cover;transform:scaleX(-1)}
        .selfie-camera-actions{display:grid;grid-template-columns:1fr;gap:10px}
        .report-form-shell{display:grid;grid-template-columns:minmax(0,1.15fr) minmax(280px,.85fr);gap:20px}
        .report-form-main,.report-form-side,.capture-helper-grid{display:grid;gap:16px}
        .form-step-card{background:linear-gradient(135deg,rgba(255,255,255,.98),rgba(242,248,255,.96))}
        .form-step-label{background:rgba(201,28,33,.10);color:var(--accent)}
        .capture-action-card{position:relative;cursor:pointer;text-align:left;background:var(--accent);border:1px solid rgba(159,19,24,.28);box-shadow:none;color:#fff;transition:transform .18s ease,border-color .18s ease}
        .capture-action-card:hover,.capture-action-card:focus-visible{transform:translateY(-1px);border-color:rgba(159,19,24,.46);box-shadow:none}
        .capture-action-card strong,.capture-action-card p{color:#fff}
        .capture-action-card p{color:rgba(255,255,255,.84)}
        .capture-action-card .tag{justify-self:start;background:rgba(255,255,255,.14);color:#fff}
        .capture-action-card .tag.green,.capture-action-card .tag.blue,.capture-action-card .tag.red,.capture-action-card .tag.amber,.capture-action-card .tag.neutral{background:rgba(255,255,255,.16);color:#fff}
        .inline-warning-card{background:linear-gradient(135deg,rgba(255,247,236,.98),rgba(255,239,229,.96));border-color:rgba(217,119,6,.22);box-shadow:0 16px 30px rgba(217,119,6,.09)}
        .inline-warning-card[data-warning-tone="red"]{background:linear-gradient(135deg,rgba(255,243,243,.99),rgba(255,231,231,.97));border-color:rgba(185,28,28,.24);box-shadow:0 18px 36px rgba(185,28,28,.11)}
        .warning-reason-list{margin:0;padding-left:1rem;display:grid;gap:6px}
        .warning-reason-list li{color:var(--ink);line-height:1.55}
        .warning-actions{gap:10px;flex-wrap:wrap}
        .warning-actions .btn{flex:1 1 180px}
        .form-readiness-card{background:linear-gradient(180deg,rgba(255,250,250,.98),rgba(255,241,241,.96));border-color:rgba(201,28,33,.14)}
        .requirement-item .tag{justify-self:start}
        .report-submit-card{position:sticky;top:20px}
        .btn[disabled],.action-btn[disabled],.pill-btn[disabled]{opacity:.58;cursor:not-allowed;transform:none;box-shadow:none}
        .command-hero{padding:22px;border-radius:28px;background:linear-gradient(135deg,#fff 0%,#f8fbff 58%,#fff3f3 100%);border:1px solid rgba(15,31,47,.10);box-shadow:var(--shadow-soft);display:grid;grid-template-columns:minmax(0,.92fr) minmax(420px,1.08fr);gap:18px;align-items:stretch}
        .command-hero-main{padding:8px 6px;display:grid;align-content:center;gap:12px}
        .command-hero-main h2{margin:0;font-family:"Bahnschrift","Trebuchet MS",sans-serif;font-size:clamp(2rem,4vw,3.4rem);line-height:.98;letter-spacing:-.06em}
        .command-hero-main p{margin:0;color:var(--muted);line-height:1.65;max-width:62ch}
        .command-action-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:10px}
        .command-action{min-width:0;min-height:132px;padding:16px;border-radius:22px;background:#fff;border:1px solid rgba(15,31,47,.10);box-shadow:none;color:var(--ink);display:grid;align-content:space-between;gap:10px;text-align:left;cursor:pointer;transition:transform .18s ease,border-color .18s ease,box-shadow .18s ease}
        .command-action:hover,.command-action:focus-visible{transform:translateY(-2px);border-color:rgba(201,28,33,.22);box-shadow:0 16px 32px rgba(12,25,39,.08);outline:0}
        .command-action.primary{background:linear-gradient(135deg,var(--accent),var(--accent-strong));color:#fff;border-color:rgba(159,19,24,.25)}
        .command-action span{display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:14px;background:rgba(15,31,47,.06);font-weight:900}
        .command-action.primary span{background:rgba(255,255,255,.18)}
        .command-action strong{font-size:1rem;line-height:1.2}
        .command-action small{color:var(--muted);font-weight:800;line-height:1.35}
        .command-action.primary small{color:rgba(255,255,255,.82)}
        .command-stat-grid{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:12px}
        .command-stat{padding:16px;border-radius:20px;background:#fff;border:1px solid rgba(15,31,47,.09);box-shadow:var(--shadow-soft);display:grid;gap:9px;min-width:0}
        .command-stat span,.command-mini-metrics span{font-size:.7rem;font-weight:900;letter-spacing:.14em;text-transform:uppercase;color:var(--muted)}
        .command-stat strong,.command-mini-metrics strong{font-family:"Bahnschrift","Trebuchet MS",sans-serif;font-size:2.1rem;line-height:1;letter-spacing:-.05em}
        .command-stat p{margin:0;color:var(--muted);font-size:.88rem;line-height:1.5}
        .command-stat.urgent{border-color:rgba(209,139,31,.22);background:linear-gradient(180deg,#fff,rgba(255,248,232,.92))}
        .command-stat.danger{border-color:rgba(199,38,38,.20);background:linear-gradient(180deg,#fff,rgba(255,241,241,.94))}
        .command-layout{display:grid;grid-template-columns:minmax(0,1fr) minmax(320px,.38fr);gap:18px;align-items:start}
        .command-layout.reverse{grid-template-columns:minmax(0,.68fr) minmax(320px,.32fr)}
        .command-side-stack,.command-review-list,.command-person-list,.command-audit-list{display:grid;gap:12px}
        .command-two-column{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;align-items:start}
        .command-focus-panel,.command-compact-panel{background:rgba(255,255,255,.9)}
        .command-panel-head{align-items:flex-start}
        .command-panel-head .btn{flex:0 0 auto}
        .command-review-card,.command-person-card,.command-priority-card,.command-empty-state,.command-audit-card,.command-readiness-list div,.command-mini-metrics div{border:1px solid rgba(15,31,47,.09);background:#fff;border-radius:18px;box-shadow:none}
        .command-review-card{padding:16px;display:grid;grid-template-columns:minmax(0,1fr) auto;gap:12px;align-items:start;transition:transform .18s ease,border-color .18s ease,box-shadow .18s ease}
        .command-review-card:hover,.command-review-card:focus-visible{transform:translateY(-1px);border-color:rgba(32,104,174,.18);box-shadow:0 14px 28px rgba(12,25,39,.06);outline:0}
        .command-review-card.compact{grid-template-columns:1fr}
        .command-review-main{display:grid;gap:7px;min-width:0}
        .command-review-main h3{margin:0;font-family:"Bahnschrift","Trebuchet MS",sans-serif;font-size:1.25rem;line-height:1.1;letter-spacing:-.03em;overflow-wrap:anywhere}
        .command-review-main p,.command-review-card .detail-copy{margin:0;color:var(--muted);line-height:1.55}
        .command-review-meta{display:flex;justify-content:flex-end;align-items:flex-start;gap:8px;flex-wrap:wrap}
        .command-review-footer{grid-column:1 / -1;display:flex;align-items:center;justify-content:space-between;gap:12px;padding-top:10px;border-top:1px solid rgba(15,31,47,.08);color:var(--muted);font-size:.88rem;font-weight:800}
        .command-review-footer strong{color:var(--blue)}
        .command-priority-card,.command-empty-state{padding:16px;display:grid;gap:12px}
        .command-priority-card p,.command-empty-state p{margin:0;color:var(--muted);line-height:1.6}
        .command-empty-state{border-style:dashed;background:var(--surface-alt)}
        .command-empty-state.compact{min-height:150px;align-content:center}
        .command-readiness-list{display:grid;gap:10px}
        .command-readiness-list div{padding:13px 14px;display:flex;justify-content:space-between;gap:14px;align-items:flex-start}
        .command-readiness-list strong{font-size:.92rem}
        .command-readiness-list span{color:var(--muted);text-align:right;font-size:.88rem;line-height:1.45}
        .command-person-card{padding:14px 16px;display:grid;grid-template-columns:minmax(0,1fr) auto;gap:12px;align-items:center}
        .command-person-card strong{display:block;font-size:1rem;line-height:1.3}
        .command-person-card p{margin:4px 0 0;color:var(--muted);font-size:.9rem;line-height:1.45}
        .command-map-shell{padding:0;border:0;background:transparent}
        .command-map-shell .map-canvas{min-height:460px;border-radius:22px}
        .command-mini-metrics{display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:10px}
        .command-mini-metrics.two{grid-template-columns:repeat(2,minmax(0,1fr))}
        .command-mini-metrics div{padding:14px;display:grid;gap:8px}
        .command-audit-card{padding:14px 16px;display:grid;grid-template-columns:14px minmax(0,1fr);gap:12px}
        .command-audit-card > span{width:12px;height:12px;border-radius:999px;background:var(--blue);box-shadow:0 0 0 7px rgba(32,104,174,.10);margin-top:5px}
        .command-audit-card strong{display:block;line-height:1.3}
        .command-audit-card p{margin:6px 0;color:var(--muted);line-height:1.55}
        .command-audit-card small{display:block;margin-top:8px;color:var(--muted);font-size:.76rem;font-weight:900;letter-spacing:.10em;text-transform:uppercase}
        .mobile-quick-nav{display:none}
        .profile-shell-grid{align-items:start}
        .profile-photo-grid{grid-template-columns:minmax(0,180px) minmax(0,1fr);align-items:start}
        .civilian-account-summary-grid{grid-template-columns:repeat(4,minmax(0,1fr))}
        .civilian-account-form-grid{grid-template-columns:repeat(5,minmax(0,1fr))}
        .civilian-account-action-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
        @media (min-width:981px) and (max-width:1366px){
            .ops-main{padding:16px 16px 118px}
            .topbar,.hero-card,.panel{padding:18px}
            .topbar{gap:14px}
            .topbar-copy h1{font-size:clamp(1.85rem,3vw,2.7rem)}
            .topbar-actions{gap:10px}
            .system-strip{grid-template-columns:repeat(3,minmax(0,1fr))}
            .summary-strip,.settings-grid,.preview-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
            .workspace-side{grid-template-columns:repeat(2,minmax(0,1fr));align-items:start}
            .workspace-side > .panel:first-child{grid-column:1 / -1}
            .responder-feed-card .preview-card:last-child{grid-column:1 / -1}
            .responder-feed-card .action-row{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;align-items:stretch}
            .responder-feed-card .action-row form{display:block}
            .responder-feed-card .action-row > *,.responder-feed-card .action-row form > *{min-width:0}
            .responder-feed-card .action-btn,.responder-feed-card .btn{width:100%}
            .command-hero{grid-template-columns:1fr}
            .command-action-grid{grid-template-columns:repeat(3,minmax(0,1fr))}
            .command-stat-grid{grid-template-columns:repeat(3,minmax(0,1fr))}
            .command-layout,.command-layout.reverse,.command-two-column{grid-template-columns:1fr}
            .command-person-card{grid-template-columns:1fr}
            .civilian-account-summary-grid,.civilian-account-form-grid,.civilian-account-action-grid,.profile-photo-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
            .map-canvas{min-height:400px}
        }
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
            body.is-standalone .ops-main{padding:16px}
            body.is-standalone .hero-card,body.is-standalone .panel{padding:18px}
            body.is-standalone .mobile-quick-nav{display:none!important}
        }
        @media (max-width:1220px){
            .ops-main{padding-bottom:122px}
            .mobile-quick-nav{position:fixed;left:12px;right:12px;bottom:max(12px,env(safe-area-inset-bottom));z-index:1420;display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:8px;padding:10px;border-radius:28px;background:linear-gradient(180deg,rgba(255,255,255,.98),rgba(244,248,252,.94));border:1px solid rgba(255,255,255,.92);box-shadow:0 20px 46px rgba(12,25,39,.18),inset 0 1px 0 rgba(255,255,255,.72);backdrop-filter:blur(22px)}
            .mobile-quick-nav::before{content:"";position:absolute;inset:0;border-radius:inherit;pointer-events:none;background:linear-gradient(180deg,rgba(255,255,255,.58),transparent 34%)}
            .mobile-quick-nav > *{position:relative;z-index:1}
            .mobile-quick-nav a,.mobile-quick-nav button{display:grid;gap:6px;justify-items:center;align-content:center;min-height:70px;padding:10px 8px;border-radius:20px;color:var(--muted);font-size:.72rem;font-weight:800;letter-spacing:.05em;text-transform:uppercase;background:transparent;border:0;transition:transform .2s ease,background .2s ease,box-shadow .2s ease,color .2s ease}
            .mobile-quick-nav a strong,.mobile-quick-nav button strong{font-size:.72rem;line-height:1.2}
            .mobile-quick-nav a.active{background:linear-gradient(180deg,rgba(201,28,33,.14),rgba(201,28,33,.08));color:var(--accent);box-shadow:0 12px 22px rgba(201,28,33,.12);transform:translateY(-2px)}
            .mobile-quick-nav span,.mobile-quick-nav button span{display:inline-flex;min-width:34px;min-height:34px;align-items:center;justify-content:center;border-radius:999px;background:rgba(15,31,47,.06);font-size:.74rem;font-weight:900;box-shadow:inset 0 1px 0 rgba(255,255,255,.66);overflow:hidden}
            .mobile-quick-nav span svg,.mobile-quick-nav button span svg{width:18px;height:18px;stroke:currentColor;stroke-width:1.9;fill:none;display:block}
            .mobile-quick-nav a.active span{background:rgba(201,28,33,.14);color:var(--accent)}
            .mobile-quick-nav__logout{display:contents}
            .mobile-quick-nav button.is-logout{color:var(--danger)}
            .mobile-quick-nav button.is-logout span{background:rgba(199,38,38,.10);color:var(--danger)}
            .civilian-home-grid,.report-form-shell,.civilian-simple-hero-grid,.civilian-report-hero-grid,.civilian-success-layout,.civilian-success-flow{grid-template-columns:1fr}
            .command-hero{grid-template-columns:1fr;padding:18px;border-radius:24px}
            .command-action-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
            .command-action{min-height:116px;border-radius:18px}
            .command-stat-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
            .command-layout,.command-layout.reverse,.command-two-column{grid-template-columns:1fr}
            .command-review-card,.command-person-card{grid-template-columns:1fr}
            .command-review-meta{justify-content:flex-start}
            .command-panel-head{display:grid}
            .command-panel-head .btn{width:100%}
            .command-map-shell .map-canvas{min-height:340px}
            .civilian-simple-stats,.civilian-home-status-row,.civilian-latest-card{grid-template-columns:1fr}
            .civilian-success-cards{grid-template-columns:1fr}
            .civilian-four-button-grid,.civilian-three-button-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
        }
        @media (max-width:620px){
            .ops-main{padding:14px 14px 120px}
            .hero-card{padding:18px}
            .hero-copy h2{font-size:clamp(1.7rem,9vw,2.3rem)}
            .hero-copy p,.panel p,.section-copy,.incident-summary,.detail-copy{line-height:1.6}
            .hero-metrics,.metric-grid{gap:12px}
            .metric-card{min-height:auto;padding:16px}
            .metric-card strong{font-size:2rem}
            .mobile-quick-nav{left:10px;right:10px;gap:6px;padding:8px}
            .mobile-quick-nav a,.mobile-quick-nav button{min-height:66px;padding:8px 4px;font-size:.64rem}
            .mobile-quick-nav a strong,.mobile-quick-nav button strong{font-size:.64rem}
            .mobile-quick-nav span,.mobile-quick-nav button span{min-width:30px;min-height:30px;font-size:.68rem}
            .mobile-quick-nav span svg,.mobile-quick-nav button span svg{width:16px;height:16px}
            .pwa-update-card{top:14px;right:10px;width:min(300px,calc(100vw - 20px));padding:12px 14px}
            .command-hero-main h2{font-size:clamp(1.8rem,10vw,2.35rem)}
            .command-action-grid,.command-stat-grid,.command-mini-metrics.two{grid-template-columns:1fr}
            .command-action{min-height:auto;padding:14px}
            .command-stat{padding:14px}
            .command-stat strong,.command-mini-metrics strong{font-size:1.8rem}
            .command-review-card,.command-priority-card,.command-person-card,.command-audit-card,.command-empty-state{border-radius:16px}
            .command-readiness-list div{display:grid}
            .command-readiness-list span{text-align:left}
            .civilian-hero-callout,.civilian-helper-card,.civilian-action-card,.civilian-history-card,.form-step-card,.requirement-item{padding:16px}
            .civilian-simple-hero,.civilian-report-hero{padding:16px}
            .civilian-simple-actions,.civilian-home-primary-actions{grid-template-columns:1fr}
            .civilian-simple-stats{grid-template-columns:repeat(3,minmax(0,1fr))}
            .civilian-simple-stats article,.civilian-home-status-row article{padding:12px;border-radius:18px}
            .civilian-simple-stats span,.civilian-home-status-row span{font-size:.62rem;letter-spacing:.10em}
            .civilian-simple-stats strong,.civilian-home-status-row strong{font-size:1.45rem}
            .civilian-home-card{padding:16px;border-radius:18px}
            .civilian-home-status-row{grid-template-columns:repeat(3,minmax(0,1fr));gap:8px}
            .civilian-latest-card{grid-template-columns:1fr;align-items:stretch}
            .civilian-latest-panel .panel-head{gap:12px}
            .civilian-report-quick-list{grid-template-columns:repeat(4,minmax(0,1fr));gap:8px}
            .civilian-report-quick-list span{min-height:48px;padding:10px 6px;font-size:.62rem;letter-spacing:.10em}
            .civilian-capture-panel{padding:14px;border-radius:18px}
            .civilian-four-button-grid,.civilian-three-button-grid{gap:10px}
            .civilian-four-button-grid .capture-action-card,.civilian-three-button-grid .capture-action-card{min-height:132px;padding:12px;border-radius:16px}
            .capture-action-icon{width:36px;height:36px;border-radius:12px}
            .capture-action-card strong{font-size:.98rem}
            .capture-action-card p{font-size:.82rem;line-height:1.45}
            .civilian-mobile-status-card{padding:12px 14px}
            .civilian-compact-details .preview-grid{grid-template-columns:1fr}
            .warning-actions .btn{flex-basis:100%}
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
        $hideTopbar = trim($__env->yieldContent('hide_topbar')) === 'true';
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
        $navIcons = [
            'home' => <<<'SVG'
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10.5 12 3l9 7.5"/><path d="M5.5 9.5V21h13V9.5"/><path d="M9.5 21v-6.5h5V21"/></svg>
SVG,
            'radar' => <<<'SVG'
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="8"/><path d="M12 4v8l5 5"/><path d="M4 12h2"/><path d="M18 12h2"/></svg>
SVG,
            'report' => <<<'SVG'
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M7 3.5h7l5 5V20a1 1 0 0 1-1 1H7a2 2 0 0 1-2-2V5.5a2 2 0 0 1 2-2Z"/><path d="M14 3.5V9h5"/><path d="M9 13h6"/><path d="M9 17h4"/></svg>
SVG,
            'feed' => <<<'SVG'
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6.5h16"/><path d="M4 12h16"/><path d="M4 17.5h10"/><circle cx="18" cy="17.5" r="2"/></svg>
SVG,
            'profile' => <<<'SVG'
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M5 20a7 7 0 0 1 14 0"/></svg>
SVG,
            'settings' => <<<'SVG'
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v3"/><path d="M12 18v3"/><path d="M3 12h3"/><path d="M18 12h3"/><path d="m5.64 5.64 2.12 2.12"/><path d="m16.24 16.24 2.12 2.12"/><path d="m18.36 5.64-2.12 2.12"/><path d="m7.76 16.24-2.12 2.12"/><circle cx="12" cy="12" r="3.5"/></svg>
SVG,
            'logout' => <<<'SVG'
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M10 4H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h4"/><path d="M14 16l4-4-4-4"/><path d="M18 12H9"/></svg>
SVG,
            'shield' => <<<'SVG'
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 5 6v6c0 4.4 2.9 8.2 7 9 4.1-.8 7-4.6 7-9V6l-7-3Z"/><path d="m9.5 12 1.7 1.7 3.3-3.7"/></svg>
SVG,
        ];
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

            @unless ($hideTopbar)
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
            @endunless

            @if (isset($connectivity) && !(($dashboardMode ?? null) === 'responder' && (request()->routeIs('dashboard') || request()->routeIs('monitoring'))))
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
                <span aria-hidden="true">{!! $authUser?->is_admin ? $navIcons['shield'] : ($isCivilian ? $navIcons['home'] : $navIcons['radar']) !!}</span>
                <strong>{{ $compactPrimaryLabel }}</strong>
            </a>
            <a href="{{ $compactSecondaryUrl }}" class="{{ ($isCivilian && request()->routeIs('reports.create')) || (! $isCivilian && request()->routeIs('reports.index')) ? 'active' : '' }}">
                <span aria-hidden="true">{!! $isCivilian ? $navIcons['report'] : $navIcons['feed'] !!}</span>
                <strong>{{ $compactSecondaryLabel }}</strong>
            </a>
            <a href="{{ route('profile.show') }}" class="{{ request()->routeIs('profile.*') ? 'active' : '' }}">
                <span aria-hidden="true">{!! $navIcons['profile'] !!}</span>
                <strong>Profile</strong>
            </a>
            <a href="{{ route('settings.index') }}" class="{{ request()->routeIs('settings.*') ? 'active' : '' }}">
                <span aria-hidden="true">{!! $navIcons['settings'] !!}</span>
                <strong>Settings</strong>
            </a>
            <form method="POST" action="{{ route('logout') }}" class="mobile-quick-nav__logout">
                @csrf
                <button type="submit" class="is-logout" aria-label="Log out">
                    <span aria-hidden="true">{!! $navIcons['logout'] !!}</span>
                    <strong>Logout</strong>
                </button>
            </form>
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


