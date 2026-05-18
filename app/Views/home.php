<div class="scroll-progress" id="scrollProgressBar"></div>

<div class="experience-page-wrap">
    <section id="hero" class="experience-hero reveal-section is-visible">
        <div class="hero-parallax-layer" id="heroParallaxLayer" aria-hidden="true"></div>
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <span class="experience-kicker">Football Reservation Platform</span>
            <h1>Reserve the best 5v5 pitch in your city with one premium flow.</h1>
            <p>
                Book in minutes, pay safely with wallet or tickets, and verify every reservation with a unique booking code at the venue.
            </p>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= route_path('/pitches') ?>" class="btn btn-warning btn-lg rounded-pill px-4">Explore Pitches</a>
                <?php if (!Auth::check()): ?>
                    <a href="<?= route_path('/auth/register') ?>" class="btn btn-outline-light btn-lg rounded-pill px-4">Create Account</a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <nav class="experience-anchor-nav" id="experienceAnchorNav">
        <a href="#hero" class="anchor-link active">Hero</a>
        <a href="#features" class="anchor-link">Features</a>
        <a href="#gallery" class="anchor-link">Gallery</a>
        <a href="#stats" class="anchor-link">Stats</a>
        <a href="#flow" class="anchor-link">Flow</a>
        <a href="#cta" class="anchor-link">Start</a>
    </nav>

    <section id="features" class="experience-section reveal-section">
        <div class="section-head">
            <h2>How the platform is used daily</h2>
            <p>Players reserve by hour, while admins manage pitch availability, support, and payments.</p>
        </div>
        <div class="row g-3">
            <div class="col-md-6 col-xl-4">
                <article class="glass-panel p-4 h-100">
                    <h3 class="h5 text-warning">Slot Locking</h3>
                    <p class="mb-0 text-light-emphasis">When one user starts booking an hour, that slot is temporarily locked to avoid double booking.</p>
                </article>
            </div>
            <div class="col-md-6 col-xl-4">
                <article class="glass-panel p-4 h-100">
                    <h3 class="h5 text-warning">Wallet + Tickets</h3>
                    <p class="mb-0 text-light-emphasis">Users top up balance, convert to tickets, and pay their share for 5v5 matches.</p>
                </article>
            </div>
            <div class="col-md-6 col-xl-4">
                <article class="glass-panel p-4 h-100">
                    <h3 class="h5 text-warning">Unique Booking Code</h3>
                    <p class="mb-0 text-light-emphasis">At check-in, the admin validates one reservation code linked to date, hour, and pitch.</p>
                </article>
            </div>
            <div class="col-md-6 col-xl-4">
                <article class="glass-panel p-4 h-100">
                    <h3 class="h5 text-warning">Direct Slot Reservation</h3>
                    <p class="mb-0 text-light-emphasis">Players can reserve available slots directly with clear availability and live status.</p>
                </article>
            </div>
            <div class="col-md-6 col-xl-4">
                <article class="glass-panel p-4 h-100">
                    <h3 class="h5 text-warning">Map Directions</h3>
                    <p class="mb-0 text-light-emphasis">Users can view route distance and travel time to each pitch before booking.</p>
                </article>
            </div>
            <div class="col-md-6 col-xl-4">
                <article class="glass-panel p-4 h-100">
                    <h3 class="h5 text-warning">Admin Pitch Tools</h3>
                    <p class="mb-0 text-light-emphasis">Admins monitor reservations, occupancy, cancellations, and revenue from one screen.</p>
                </article>
            </div>
        </div>
    </section>

    <section id="gallery" class="experience-section reveal-section">
        <div class="section-head">
            <h2>Local 5v5 Pitch Photos</h2>
            <p>Examples of small football terrains like the ones players usually book.</p>
        </div>
        <?php
        $galleryImages = [
            ['src' => '/assets/images/stadium-1.jpg', 'alt' => 'Small fenced football pitch seen from above'],
            ['src' => '/assets/images/stadium-2.jpg', 'alt' => '5v5 artificial turf pitch with side goal'],
            ['src' => '/assets/images/stadium-3.jpg', 'alt' => 'Community mini-football field with small goals'],
            ['src' => '/assets/images/stadium-4.jpg', 'alt' => 'Local turf field with compact goal posts'],
            ['src' => '/assets/images/stadium-5.jpg', 'alt' => 'Urban small-sided pitch during sunset'],
            ['src' => '/assets/images/stadium-6.jpg', 'alt' => 'Indoor-style futsal surface with footballs'],
        ];
        ?>
        <div class="row g-3">
            <?php foreach ($galleryImages as $image): ?>
                <div class="col-md-6 col-xl-4">
                    <figure class="gallery-item">
                        <div class="image-skeleton"></div>
                        <img
                            class="lazy-image"
                            src="<?= route_path($image['src']) ?>"
                            alt="<?= e($image['alt']) ?>"
                            loading="lazy"
                            decoding="async"
                        >
                    </figure>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section id="stats" class="experience-section reveal-section">
        <div class="section-head">
            <h2>Booking Basics</h2>
            <p>Quick info players check before reserving a match slot.</p>
        </div>
        <div class="row g-3">
            <div class="col-6 col-lg-3">
                <div class="stat-card">
                    <strong>24/7</strong>
                    <span>Access to bookings</span>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-card">
                    <strong>10</strong>
                    <span>Players per full match</span>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-card">
                    <strong>50 DH</strong>
                    <span>Fixed price per player slot</span>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-card">
                    <strong>48h</strong>
                    <span>Clear cancellation window</span>
                </div>
            </div>
        </div>
    </section>

    <section id="flow" class="experience-section reveal-section">
        <div class="section-head">
            <h2>Simple Match Flow</h2>
            <p>A clear path from search to check-in.</p>
        </div>
        <div class="row g-3">
            <div class="col-md-3">
                <div class="flow-step">
                    <span>01</span>
                    <h3>Find Pitch</h3>
                    <p>Search by day, hour, location, and distance.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="flow-step">
                    <span>02</span>
                    <h3>Lock Slot</h3>
                    <p>Reserve the hour with real-time conflict protection.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="flow-step">
                    <span>03</span>
                    <h3>Pay Team Share</h3>
                    <p>Use wallet or tickets for a smooth 5v5 payment flow.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="flow-step">
                    <span>04</span>
                    <h3>Verify Code</h3>
                    <p>Admin validates your unique booking code at arrival.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="cta" class="experience-section reveal-section cta-section">
        <h2>Ready to run matches with prestige and control?</h2>
        <p class="text-light-emphasis">Start now and manage your next game in a clean, trusted football experience.</p>
        <div class="d-flex flex-wrap justify-content-center gap-2">
            <a href="<?= route_path('/pitches') ?>" class="btn btn-warning btn-lg rounded-pill px-4">Book a Pitch</a>
            <?php if (!Auth::check()): ?>
                <a href="<?= route_path('/auth/login') ?>" class="btn btn-outline-light btn-lg rounded-pill px-4">Login</a>
            <?php endif; ?>
        </div>
    </section>
</div>

<button id="backToTopBtn" class="back-to-top-btn" type="button" aria-label="Back to top">Top</button>

