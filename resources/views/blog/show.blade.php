@extends('layouts.app', ['title' => $post->title . ' - Camping Vibes'])

@section('content')
    <article>
        <header class="article-hero">
            <img src="{{ $post->imageUrl() }}" alt="{{ $post->title }}">
            <div class="shell">
                <span class="eyebrow">Journal</span>
                <h1>{{ $post->title }}</h1>
                <p>{{ optional($post->published_at)->translatedFormat('d F Y') }}</p>
            </div>
        </header>

        <section class="section">
            <div class="article-content">
                {!! $post->content !!}
            </div>
        </section>
    </article>

    @if ($morePosts->isNotEmpty())
        <section class="section section-soft">
            <div class="shell">
                <div class="section-head">
                    <div>
                        <span class="eyebrow">À lire aussi</span>
                        <h2>Continuer l'exploration</h2>
                    </div>
                </div>
                <div class="grid grid-3">
                    @foreach ($morePosts as $morePost)
                        @include('partials.article-card', ['post' => $morePost])
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection

