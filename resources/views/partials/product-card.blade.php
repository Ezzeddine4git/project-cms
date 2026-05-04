<article class="product-card">
    <a href="{{ route('products.show', $product) }}">
        <div class="product-media">
            <img src="{{ $product->mainPhotoUrl() }}" alt="{{ $product->name }}">
        </div>
    </a>
    <div class="card-body">
        <div class="meta">{{ $product->category?->name ?? 'Camping' }}</div>
        <a href="{{ route('products.show', $product) }}">
            <h3 class="card-title">{{ $product->name }}</h3>
        </a>
        <p class="muted">{{ \Illuminate\Support\Str::limit(strip_tags($product->description), 90) }}</p>
        <div class="price-line">
            <span class="price">{{ \App\Support\Money::format($product->price) }}</span>
            <span class="badge">{{ $product->stock_quantity > 0 ? 'En stock' : 'Rupture' }}</span>
        </div>
    </div>
</article>
