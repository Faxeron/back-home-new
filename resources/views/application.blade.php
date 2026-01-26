<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <link rel="icon" href="{{ asset('favicon.ico') }}" />
  <meta name="robots" content="noindex, nofollow" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Vuexy - Vuejs Admin Dashboard Template</title>
  <link rel="stylesheet" type="text/css" href="{{ asset('loader.css') }}" />
  @vite(['resources/ts/main.ts'])
</head>

<body>
  <div id="app">
    <div id="loading-bg">
      <div class="loading-logo">
        <img
          id="loading-logo-img"
          src="{{ asset('images/logos/logo_septik_sever.png') }}"
          data-light="{{ asset('images/logos/logo_septik_sever.png') }}"
          data-dark="{{ asset('images/logos/logo_septik_sever_dark.png') }}"
          alt="Septik Sever"
        />
      </div>
      <div class=" loading">
        <div class="effect-1 effects"></div>
        <div class="effect-2 effects"></div>
        <div class="effect-3 effects"></div>
      </div>
    </div>
  </div>
  
  <script>
    const loaderColor = localStorage.getItem('vuexy-initial-loader-bg') || '#FFFFFF'
    const primaryColor = localStorage.getItem('vuexy-initial-loader-color') || '#7367F0'

    const getCookieBySuffix = suffix => {
      const cookies = document.cookie ? document.cookie.split(';') : []
      for (const raw of cookies) {
        const [key, value] = raw.trim().split('=')
        if (key && key.endsWith(suffix))
          return decodeURIComponent(value || '')
      }
      return null
    }

    const resolveLoaderTheme = () => {
      const theme = getCookieBySuffix('-theme') || 'system'
      if (theme === 'light' || theme === 'dark')
        return theme

      const scheme = getCookieBySuffix('-color-scheme')
      if (scheme === 'light' || scheme === 'dark')
        return scheme

      return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
    }

    const logoImg = document.getElementById('loading-logo-img')
    if (logoImg) {
      const theme = resolveLoaderTheme()
      const lightSrc = logoImg.getAttribute('data-light')
      const darkSrc = logoImg.getAttribute('data-dark')
      logoImg.setAttribute('src', theme === 'dark' ? (darkSrc || lightSrc) : (lightSrc || darkSrc))
    }

    if (loaderColor)
      document.documentElement.style.setProperty('--initial-loader-bg', loaderColor)
    if (loaderColor)
      document.documentElement.style.setProperty('--initial-loader-bg', loaderColor)

    if (primaryColor)
      document.documentElement.style.setProperty('--initial-loader-color', primaryColor)
    </script>
  </body>
</html>
