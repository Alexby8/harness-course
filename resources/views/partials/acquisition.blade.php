<div hidden data-acquisition
     data-endpoint="{{ route('acquisition.store', absolute: false) }}"
     data-consent-endpoint="{{ route('cookie-consent.show', absolute: false) }}"
     data-version="{{ config('cookie-consent.version') }}"
     data-csrf="{{ csrf_token() }}"
     data-capture="{{ request()->routeIs('home', 'docs', 'guides.*', 'register') ? '1' : '0' }}"
     data-parameters="{{ json_encode(array_keys(config('acquisition.parameters'))) }}"></div>
