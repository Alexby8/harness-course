<figure class="guide-illustration">
    <div @class(['guide-illustration-desktop' => ! empty($image['mobile_src'])])>
        <img src="{{ $image['src'] }}" alt="{{ $image['alt'] }}" width="{{ $image['width'] }}" height="{{ $image['height'] }}" loading="lazy" decoding="async" style="max-width: 100%; height: auto;">
    </div>
    @if(! empty($image['mobile_src']))
        <div class="guide-illustration-mobile">
            <img src="{{ $image['mobile_src'] }}" alt="{{ $image['alt'] }}" width="{{ $image['mobile_width'] }}" height="{{ $image['mobile_height'] }}" loading="lazy" decoding="async" style="max-width: 100%; height: auto;">
        </div>
    @endif
    @if(! empty($image['caption']))
        <figcaption>{{ $image['caption'] }}</figcaption>
    @endif
</figure>
