<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'Dashboard') ·Examination System</title>
<style>
:root{--bg:#f4f6fb;--card:#fff;--text:#1b2433;--muted:#5d6b82;--border:#d9dfeb;--accent:#2f5496;--accent-t:#fff;--soft:#e8effc;--ok:#1e8449;--warn:#b7791f;color-scheme:light}
@media (prefers-color-scheme:dark){:root{--bg:#0f141d;--card:#18202d;--text:#e8edf5;--muted:#9aa8bf;--border:#2b3648;--accent:#6c97e6;--accent-t:#0f141d;--soft:#1f2d46;--ok:#4cc38a;--warn:#e0a84a;color-scheme:dark}}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--text);font:16px/1.5 system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif}
.topbar{background:var(--card);border-bottom:1px solid var(--border)}
.topbar .in{max-width:1000px;margin:0 auto;padding:12px 16px;display:flex;align-items:center;justify-content:space-between;gap:12px}
.brand{font-weight:700;color:var(--accent)}
.who{display:flex;align-items:center;gap:12px;font-size:14px}
.page{max-width:1000px;margin:0 auto;padding:22px 16px 48px}
.grid{display:grid;grid-template-columns:1fr 300px;gap:18px;align-items:start}
@media (max-width:820px){.grid{grid-template-columns:1fr}}
.card{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:20px;margin-bottom:18px}
.card h2{font-size:17px;margin:0 0 12px}
h1{font-size:24px;margin:0 0 4px}
.muted{color:var(--muted);font-size:14px}
.badge{display:inline-block;font-size:13px;font-weight:600;border-radius:99px;padding:3px 12px;background:var(--soft);color:var(--accent)}
.badge.ok{background:transparent;color:var(--ok);border:1px solid var(--ok)}
table{width:100%;border-collapse:collapse;font-size:15px}
th,td{text-align:left;padding:9px 8px;border-bottom:1px solid var(--border)}
th{font-size:13px;color:var(--muted);font-weight:600}
td.n,th.n{text-align:right;font-variant-numeric:tabular-nums}
tfoot td{font-weight:700;border-bottom:0}
.stats{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-top:14px}
.stat{background:var(--soft);border-radius:10px;padding:10px 12px}
.stat b{display:block;font-size:20px}
.stat span{font-size:12px;color:var(--muted)}
.rules{padding-left:20px;margin:0 0 14px}.rules li{margin:6px 0}
.row{display:flex;justify-content:space-between;gap:12px;padding:8px 0;border-bottom:1px solid var(--border);font-size:15px}
.row:last-child{border-bottom:0}
.avatar{width:56px;height:56px;border-radius:50%;background:var(--soft);color:var(--accent);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:20px;margin-bottom:10px}
.btn{font:inherit;font-weight:600;background:var(--accent);color:var(--accent-t);border:0;border-radius:10px;padding:12px 22px;cursor:pointer}
.btn:disabled{opacity:.5;cursor:not-allowed}
.btn.ghost{background:transparent;color:var(--accent);border:1.5px solid var(--accent);padding:8px 16px}
.notice{background:var(--soft);border:1px solid var(--accent);color:var(--text);border-radius:10px;padding:10px 14px;margin-bottom:18px}
.check{display:flex;gap:10px;align-items:flex-start;margin:14px 0}
.check input{margin-top:5px;width:18px;height:18px}
</style>
</head>
<body>
<header class="topbar"><div class="in">
  <div class="brand">Examination System</div>
  <div class="who">
    <span>@yield('who')</span>
    <form method="POST" action="{{ route('candidate.logout') }}">@csrf<button class="btn ghost" type="submit">Sign out</button></form>
  </div>
</div></header>
<div class="page">@yield('content')</div>
</body>
</html>
