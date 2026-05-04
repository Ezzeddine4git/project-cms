@extends('layouts.app', ['title' => 'Journal - Camping Vibes'])

@section('content')
    <section class="page-title">
        <div class="shell">
            <span class="eyebrow">Journal</span>
            <h1>Inspirations outdoor</h1>
            <p>Guides, conseils et récits courts pour composer un campement confortable et partir plus sereinement.</p>
        </div>
    </section>

    <section class="section section-soft section-after-title">
        <div class="shell">
            <div class="grid grid-3">
                @forelse ($posts as $post)
                    @include('partials.article-card', ['post' => $post])
                @empty
                    <div class="panel">
                        <h2>Aucun article publié</h2>
                        <p class="muted">Les prochains guides apparaîtront ici.</p>
                    </div>
                @endforelse
            </div>

            <div style="margin-top: 28px">
                {{ $posts->links() }}
            </div>
        </div>
    </section>
@endsection

