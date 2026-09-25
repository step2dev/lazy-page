<article>
    <h1>{{ $page->title }}</h1>

    @if($page->description)
        <div>{{ $page->description }}</div>
    @endif

    <div>{!! $page->content !!}</div>
</article>
