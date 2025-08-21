<?php
$banners = getBanners($pdo);
$news = getNews($pdo);

if (empty($banners)) {
    $banners = [
        [
            'image_url' => 'https://www.deinfinity.com/images/hosting_banner.jpg',
            'title' => 'Onimai Cloud',
            'description' => 'ยินดีต้อนรับ'
        ],
        [
            'image_url' => 'https://www.deinfinity.com/images/hosting_banner.jpg',
            'title' => 'Onimai Cloud',
            'description' => 'ยินดีต้อนรับ'
        ],
    ];
}
if (empty($news)) {
    $news = [
        [
            'title' => 'Welcome to Onimai Cloud',
            'content' => 'We are launching our new hosting service!',
            'created_at' => date('Y-m-d H:i:s')
        ]
    ];
}
?>

<div class="card shadow mb-4">
    <div class="card-body p-0">
        <div id="carouselBanner" class="carousel slide" data-ride="carousel">
            <ol class="carousel-indicators">
                <?php for($i = 0; $i < count($banners); $i++): ?>
                <li data-target="#carouselBanner" data-slide-to="<?php echo $i; ?>" <?php echo $i === 0 ? 'class="active"' : ''; ?>></li>
                <?php endfor; ?>
            </ol>
            <div class="carousel-inner">
                <?php foreach($banners as $index => $banner): ?>
                <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                    <img src="<?php echo htmlspecialchars($banner['image_url']); ?>" class="d-block w-100" alt="Banner">
                    <div class="carousel-caption d-none d-md-block" style="background: rgba(0,0,0,0.5); border-radius: 10px; padding: 20px;">
                        <h2><?php echo htmlspecialchars($banner['title']); ?></h2>
                        <p><?php echo htmlspecialchars($banner['description']); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <a class="carousel-control-prev" href="#carouselBanner" role="button" data-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="sr-only">Previous</span>
            </a>
            <a class="carousel-control-next" href="#carouselBanner" role="button" data-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="sr-only">Next</span>
            </a>
        </div>
    </div>
</div>

<style>
.carousel-item {
    height: 400px; 
}

.carousel-item img {
    object-fit: cover;
    height: 100%;
}

.carousel-caption {
    bottom: 20%;
}

.carousel-caption h2 {
    font-size: 2.5rem;
    font-weight: 700;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
}

.carousel-caption p {
    font-size: 1.2rem;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
}

.carousel-indicators {
    bottom: 0;
}

.carousel-indicators li {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    margin: 0 5px;
}

.carousel-item h2,
.carousel-item p {
    opacity: 0;
    transform: translateY(20px);
    transition: all 0.5s ease-out;
}

.carousel-item.active h2 {
    opacity: 1;
    transform: translateY(0);
    transition-delay: 0.3s;
}

.carousel-item.active p {
    opacity: 1;
    transform: translateY(0);
    transition-delay: 0.5s;
}
</style>

<script>
$(document).ready(function(){
    $('#carouselBanner').carousel({
        interval: 5000
    });

    $('.carousel-indicators li').click(function(){
        $(this).closest('.carousel').carousel($(this).index());
    });

    $(document).on('keydown', function(e) {
        if(e.keyCode === 37) {
            $('#carouselBanner').carousel('prev');
        }
        else if(e.keyCode === 39) {
            $('#carouselBanner').carousel('next');
        }
    });

    $('#carouselBanner').hover(function(){
        $(this).carousel('pause');
    }, function(){
        $(this).carousel('cycle');
    });
});
</script>

<section class="news-section mt-4">
    <h2 class="fw-bold mb-4">📰 ข่าวสารล่าสุด</h2>

	<div class="row">
		<div class="col-12 col-md-8">
		<?php foreach($news as $item): ?>
		<div class="card mb-4 border-0 shadow-sm">
			<div class="card-body">
				<div class="d-flex align-items-start">
					<?php if (!empty($item['image_url'])): ?>
					<img src="<?= htmlspecialchars($item['image_url']) ?>" class="rounded me-3" alt="News Image" style="width: 100px; height: 100px; object-fit: cover;">
					<?php endif; ?>

					<div class="flex-grow-1">
						<h5 class="fw-semibold text-primary mb-2"><?= htmlspecialchars($item['title']) ?></h5>
						<p class="text-muted small mb-2">
							📅 <?= date('d/m/Y H:i', strtotime($item['created_at'])) ?>
						</p>
						<p><?= nl2br($item['content']) ?></p>
					</div>
				</div>
			</div>
		</div>
		<?php endforeach; ?>
		</div>
		<div class="col-12 col-md-4">
			<div class="card mb-4 border-0 shadow-sm">
				<div class="card-body">
					<p>NEW UPDATE SOON!</p>
				</div>
			</div>
		</div>
    </div>
</section>

