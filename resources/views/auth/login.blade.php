<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="/css/master.css">
    <link rel="icon" href="{{ asset('images/' . ($app_settings->favicon ?? 'favicon.ico')) }}">
    <title>{{ $app_settings->app_name ?? 'Stocky | Ultimate Inventory With POS' }}</title>

    <style>
      :root {
        color-scheme: light;
        font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        --surface: #ffffff;
        --primary: #4c44ec;
        --primary-dark: #312fab;
        --primary-soft: rgba(76,68,236,0.12);
        --text: #1f2937;
        --text-muted: #6b7280;
        --border: #e5e7eb;
        --danger: #ef4444;
        --danger-soft: rgba(239,68,68,0.10);
        --danger-border: rgba(239,68,68,0.35);
        --success: #16a34a;
        --success-soft: rgba(22,163,74,0.10);
        --success-border: rgba(22,163,74,0.35);
      }

      *, *::before, *::after { box-sizing: border-box; }
      body {
        margin: 0;
        background: linear-gradient(120deg, #f2ebff 0%, #f3f3ff 40%, #f3e8ff 100%);
        color: var(--text);
        overflow-x: hidden;
      }

      /* MAIN GRID */
      .auth-page {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        min-height: 100dvh;
      }

      /* HERO SIDE */
      .auth-hero {
        background: linear-gradient(140deg, #7a4dff 0%, #6237ff 45%, #4f2bf8 100%);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        padding: 80px clamp(40px, 8vw, 100px);
      }

      .hero-content {
        max-width: 440px;
        display: grid;
        gap: 1rem;
        text-align: left;
      }

      .hero-title {
        font-size: clamp(1.8rem, 4vw, 2.8rem);
        font-weight: 700;
        margin: 0;
      }

      .hero-subtitle {
        font-size: clamp(0.9rem, 2vw, 1rem);
        color: rgba(255,255,255,0.85);
        line-height: 1.6;
      }

      /* PANEL SIDE */
      .auth-panel {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: clamp(2rem, 6vw, 5rem);
      }

      .auth-panel-inner {
        background: var(--surface);
        border-radius: 24px;
        width: 100%;
        max-width: 420px;
        padding: clamp(1.5rem, 4vw, 3rem);
        box-shadow: 0 18px 36px -12px rgba(0,0,0,0.1);
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
      }

      .panel-title {
        font-size: clamp(1.5rem, 5vw, 1.9rem);
        margin: 0;
      }

      .panel-subtitle {
        font-size: clamp(0.85rem, 3vw, 0.95rem);
        color: var(--text-muted);
        line-height: 1.6;
        margin: 0;
      }

      /* FORM FIELDS */
      form { display: grid; gap: 1rem; }
      .field { display: grid; gap: 0.5rem; }

      .input-shell {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        border: 1px solid var(--border);
        border-radius: 999px;
        padding: 0 1rem;
        background: #f9fafb;
      }

      .input-shell input {
        flex: 1;
        border: none;
        background: transparent;
        padding: 0.8rem 0;
        font-size: 1rem;
      }

      .input-shell input:focus {
        outline: none;
      }

      .toggle-password {
        border: none;
        background: none;
        color: var(--primary);
        font-size: 0.8rem;
        cursor: pointer;
      }

      .auth-btn {
        padding: 0.9rem;
        border-radius: 999px;
        border: none;
        background: linear-gradient(135deg, #7c3aed, #4f46e5);
        color: #fff;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
      }

      .auth-btn:hover { filter: brightness(1.05); }

      .auth-link {
        color: var(--primary);
        text-decoration: none;
        font-weight: 600;
      }

      .auth-link:hover { text-decoration: underline; }

      .form-meta {
        display: flex;
        justify-content: flex-end;
        flex-wrap: wrap;
        font-size: 0.85rem;
        gap: 0.5rem;
      }

      /* RESPONSIVE */
      @media (max-width: 1024px) {
        .auth-page {
          grid-template-columns: 1fr;
        }
        .auth-hero { display: none; }
      }

      @media (max-width: 768px) {
        .auth-panel { padding: 2rem; }
        .auth-panel-inner { padding: 1.5rem; border-radius: 20px; }
      }

      @media (max-width: 480px) {
        body { background: #f9f9ff; }
        .auth-panel { padding: 1.25rem; }
        .auth-panel-inner {
          max-width: 100%;
          border-radius: 18px;
          padding: 1.25rem;
          gap: 1.2rem;
        }
        .panel-title { font-size: 1.4rem; }
        .panel-subtitle { font-size: 0.8rem; }
        .input-shell input { font-size: 0.9rem; padding: 0.75rem 0; }
        .auth-btn { font-size: 0.95rem; padding: 0.8rem; }
      }

      @media (max-width: 360px) {
        .auth-panel-inner {
          padding: 1rem;
          border-radius: 14px;
          gap: 1rem;
        }
        .panel-title { font-size: 1.2rem; }
        .auth-btn { font-size: 0.9rem; width: 100%; }
      }

      /* Alerts */
    .auth-alert{
      padding: 0.875rem 1rem;
      border-radius: 12px;
      border: 1px solid var(--border);
      font-size: 0.95rem;
      line-height: 1.5;
      background: #fff;
      margin: 0.75rem 0;
    }
    .auth-alert ul{ margin: 0; padding-left: 1.1rem; }
    .auth-alert.error{
      background: var(--danger-soft);
      border-color: var(--danger-border);
      color: #991b1b; /* dark red text for contrast */
    }
    .auth-alert.success{
      background: var(--success-soft);
      border-color: var(--success-border);
      color: #065f46;
    }

    </style>
  </head>

  <body>
    <div class="auth-page">
      <section class="auth-hero">
        <div class="hero-content">
          <h1 class="hero-title">Welcome back!</h1>
          <p class="hero-subtitle">Sign in to access your account and keep your operations in sync.</p>
        </div>
      </section>

      <section class="auth-panel">
        <div class="auth-panel-inner">
          <header>
            <h2 class="panel-title">Sign In</h2>
            <p class="panel-subtitle">Access your dashboard and manage everything from one place.</p>
          </header>

          @if (session('status'))
          <div class="auth-alert success">{{ session('status') }}</div>
          @endif

          @if ($errors->any())
          <div class="auth-alert error">
            <ul>
              @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
          @endif

          <form id="login_form" method="POST" action="{{ route('login') }}">
            @csrf
            <div class="field">
              <label for="email">Email</label>
              <div class="input-shell">
                <span class="input-addon">@</span>
                <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="you@company.com" required />
              </div>
            </div>

            <div class="field">
              <label for="password">Password</label>
              <div class="input-shell">
                <span class="input-addon">••</span>
                <input id="password" type="password" name="password" placeholder="Enter your password" required />
                <button type="button" class="toggle-password" data-target="password">Show</button>
              </div>
            </div>

            <div class="form-meta">
              <a class="auth-link" href="{{ route('password.request') }}">Forgot password?</a>
            </div>

            <button type="submit" class="auth-btn" id="login_submit_btn">
              <span class="btn-text">Sign In</span>
              <span class="btn-loading" style="display:none"><span class="spinner"></span>Verifying</span>
            </button>
          </form>
        </div>
      </section>
    </div>

    <script>
      (function() {
        const form = document.getElementById('login_form');
        const submitBtn = document.getElementById('login_submit_btn');
        const showButtons = document.querySelectorAll('.toggle-password');

        showButtons.forEach(btn => {
          btn.addEventListener('click', () => {
            const target = document.getElementById(btn.dataset.target);
            const isHidden = target.type === 'password';
            target.type = isHidden ? 'text' : 'password';
            btn.textContent = isHidden ? 'Hide' : 'Show';
          });
        });

        if (!form) return;
        let submitted = false;
        const btnText = submitBtn.querySelector('.btn-text');
        const btnLoading = submitBtn.querySelector('.btn-loading');
        form.addEventListener('submit', () => {
          if (submitted) return;
          submitted = true;
          submitBtn.disabled = true;
          btnText.style.display = 'none';
          btnLoading.style.display = 'inline-flex';
        });
      })();
    </script>
  </body>
</html>
