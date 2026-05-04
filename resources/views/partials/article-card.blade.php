<article class="article-card">
    <a href="{{ route('blog.show', $post) }}">
        <div class="article-media">
            <img src="{{ $post->imageUrl() }}" alt="{{ $post->title }}">
        </div>
    </a>
    <div class="card-body">
        <div class="meta">{{ optional($post->published_at)->translatedFormat('d F Y') ?? 'Journal' }}</div>
        <a href="{{ route('blog.show', $post) }}">
            <h3 class="card-title">{{ $post->title }}</h3>
        </a>
        <p class="muted">{{ \Illuminate\Support\Str::limit($post->excerpt ?: strip_tags($post->content), 110) }}</p>
    </div>
</article>
