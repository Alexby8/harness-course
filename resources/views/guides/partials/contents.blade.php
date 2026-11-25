<ol class="space-y-3 text-sm leading-5">
    @foreach($sections as $id => $label)
        <li><a href="#{{ $id }}" class="block text-gray-400 hover:text-indigo-300 transition">{{ $label }}</a></li>
    @endforeach
</ol>
