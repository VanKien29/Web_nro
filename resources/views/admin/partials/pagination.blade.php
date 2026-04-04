@if ($paginator->hasPages())
@foreach ($elements as $element)
@if (is_string($element))
<span style="padding:6px 12px; color:#64748b;">{{ $element }}</span>
@endif
@if (is_array($element))
@foreach ($element as $page => $url)
@if ($page == $paginator->currentPage())
<span class="active"><span>{{ $page }}</span></span>
@else
<a href="{{ $url }}">{{ $page }}</a>
@endif
@endforeach
@endif
@endforeach
@endif