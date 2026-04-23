<?php

declare(strict_types=1);

function eurohairlab_get_image_uri(string $filename): string
{
    return esc_url(get_template_directory_uri() . '/assets/images/' . ltrim($filename, '/'));
}

function eurohairlab_get_page_url(string $slug, string $fallback = ''): string
{
    $page = get_page_by_path($slug);

    if ($page instanceof WP_Post) {
        return get_permalink($page);
    }

    if ($fallback !== '') {
        return home_url($fallback);
    }

    return home_url('/' . trim($slug, '/') . '/');
}

/**
 * Permalink for the Treatments marketing page (`treatments` slug), with legacy `treatment-programs` fallback.
 */
function eurohairlab_get_treatments_page_url(): string
{
    foreach (['treatments', 'treatment-programs'] as $slug) {
        $page = get_page_by_path($slug, OBJECT, 'page');
        if ($page instanceof WP_Post) {
            return get_permalink($page);
        }
    }

    return home_url('/treatments/');
}

function eurohairlab_get_assessment_url(): string
{
    $assessment_slug = trim((string) apply_filters('eh_assessment_agent_assessment_path', 'assessment'), '/');
    if ($assessment_slug === '') {
        $assessment_slug = 'assessment';
    }
    $path = '/' . $assessment_slug;
    $home = home_url($path);

    if (defined('WP_ASSESSMENT_DOMAIN')) {
        $domain = trim((string) WP_ASSESSMENT_DOMAIN);
        if ($domain !== '') {
            $path_component = parse_url($home, PHP_URL_PATH);
            $path_s = is_string($path_component) && $path_component !== ''
                ? $path_component
                : ('/' . $assessment_slug . '/');

            return rtrim($domain, '/') . $path_s;
        }
    }

    if (defined('EUROHAIRLAB_ASSESSMENT_URL') && EUROHAIRLAB_ASSESSMENT_URL !== '') {
        return EUROHAIRLAB_ASSESSMENT_URL;
    }

    return eurohairlab_get_page_url('assessment', '/assessment/');
}

/**
 * Default target for “Free Scalp Analysis” / assessment entry CTAs on the marketing site.
 * Uses {@see eurohairlab_get_assessment_url()} (prefers {@see WP_ASSESSMENT_DOMAIN} when set) plus redirect-entry UTM parameters.
 */
function eurohairlab_get_free_scalp_analysis_default_url(): string
{
    return add_query_arg(
        [
            'utm_source' => 'redirect',
            'utm_medium' => 'web',
            'utm_campaign' => 'euro_launch',
        ],
        eurohairlab_get_assessment_url()
    );
}

/**
 * Resolve a marketing href (relative path, absolute URL, mailto, tel, #, //…).
 */
function eurohairlab_resolve_marketing_href(string $value, string $fallback = ''): string
{
    $value = trim($value);
    if ($value === '') {
        return $fallback;
    }

    if (str_starts_with($value, '//')) {
        return (is_ssl() ? 'https:' : 'http:') . $value;
    }

    if (
        str_starts_with($value, 'http://')
        || str_starts_with($value, 'https://')
        || str_starts_with($value, 'mailto:')
        || str_starts_with($value, 'tel:')
        || str_starts_with($value, '#')
    ) {
        return $value;
    }

    if (str_starts_with($value, '/')) {
        return home_url($value);
    }

    return home_url('/' . ltrim($value, '/'));
}

/**
 * Assessment CTA href: optional metabox override, otherwise {@see eurohairlab_get_free_scalp_analysis_default_url()}.
 */
function eurohairlab_resolve_free_scalp_analysis_href(string $metabox_href = ''): string
{
    $trimmed = trim($metabox_href);
    if ($trimmed !== '') {
        return eurohairlab_resolve_marketing_href($trimmed, '');
    }

    return eurohairlab_get_free_scalp_analysis_default_url();
}

function eurohairlab_url_matches_free_scalp_analysis_default(string $url): bool
{
    $url = trim($url);
    if ($url === '') {
        return false;
    }

    return untrailingslashit($url) === untrailingslashit(eurohairlab_get_free_scalp_analysis_default_url());
}

/**
 * Open in a new tab for external-style assessment links (mailto/tel/# excluded).
 */
function eurohairlab_free_scalp_analysis_link_attributes(string $url): string
{
    $url = trim($url);
    if ($url === '') {
        return '';
    }

    if (preg_match('#^(mailto:|tel:)#i', $url)) {
        return '';
    }

    if ($url === '#' || str_starts_with($url, '#')) {
        return '';
    }

    return ' target="_blank" rel="noopener noreferrer"';
}

function eurohairlab_get_primary_cta_url(): string
{
    return eurohairlab_get_page_url('contact', '/contact/') . '#contact-form';
}

/**
 * Prefer PNGs under diagnosis-mcp/, then diagnosis-ref/, else fall back URL.
 */
function eurohairlab_diagnosis_resolve_asset(string $filename, string $fallback_url): string
{
    $filename = ltrim($filename, '/');
    $bases = [
        '/assets/images/figma/diagnosis-mcp/',
        '/assets/images/figma/diagnosis-ref/',
    ];

    foreach ($bases as $base) {
        $abs = get_template_directory() . $base . $filename;
        if (is_readable($abs)) {
            return esc_url(get_template_directory_uri() . $base . $filename);
        }
    }

    return $fallback_url;
}

/**
 * Resolve a Meta Box single_image / attachment-like value to a URL string.
 *
 * @param mixed $value RWMB field value (ID, array with ID/url, or empty).
 */
function eurohairlab_mb_image_url($value, string $fallback = ''): string
{
    if (is_numeric($value)) {
        $url = wp_get_attachment_image_url((int) $value, 'full');

        return is_string($url) && $url !== '' ? $url : $fallback;
    }

    if (is_array($value)) {
        if (isset($value['ID']) && is_numeric($value['ID'])) {
            $url = wp_get_attachment_image_url((int) $value['ID'], 'full');
            if (is_string($url) && $url !== '') {
                return $url;
            }
        }

        if (isset($value['full_url']) && is_string($value['full_url']) && $value['full_url'] !== '') {
            return $value['full_url'];
        }

        if (isset($value['url']) && is_string($value['url']) && $value['url'] !== '') {
            return $value['url'];
        }
    }

    return $fallback;
}

/**
 * Permalink to the marketing Blog listing page (`blog-list`), when it exists.
 */
function eurohairlab_get_blog_list_page_url(): string
{
    $page = get_page_by_path('blog-list', OBJECT, 'page');
    if ($page instanceof WP_Post && $page->post_status === 'publish') {
        return get_permalink($page);
    }

    return trailingslashit(home_url('blog-list'));
}

function eurohairlab_get_primary_nav_items(): array
{
    return [
        ['label' => 'About', 'url' => eurohairlab_get_page_url('about', '/about/')],
        ['label' => 'Assessment', 'url' => eurohairlab_get_page_url('diagnosis', '/diagnosis/')],
        ['label' => 'Treatments', 'url' => eurohairlab_get_treatments_page_url()],
        ['label' => 'Results', 'url' => eurohairlab_get_page_url('results', '/results/')],
        ['label' => 'Promo', 'url' => eurohairlab_get_page_url('promo', '/promo/')],
        ['label' => 'Blog', 'url' => eurohairlab_get_blog_list_page_url()],
        ['label' => 'Contact', 'url' => eurohairlab_get_page_url('contact', '/contact/')],
    ];
}

function eurohairlab_get_footer_nav_groups(): array
{
    return [
        [
            ['label' => 'Treatments', 'url' => eurohairlab_get_treatments_page_url()],
            ['label' => 'Success Stories', 'url' => eurohairlab_get_page_url('results', '/results/')],
        ],
        [
            ['label' => 'Scalp Diagnosis', 'url' => eurohairlab_get_page_url('diagnosis', '/diagnosis/')],
            ['label' => 'Promo', 'url' => eurohairlab_get_page_url('promo', '/promo/')],
        ],
        [
            ['label' => 'Contact', 'url' => eurohairlab_get_page_url('contact', '/contact/')],
            ['label' => 'About Us', 'url' => eurohairlab_get_page_url('about', '/about/')],
        ],
    ];
}

function eurohairlab_get_meta_description(?WP_Post $post = null): string
{
    $post = $post instanceof WP_Post ? $post : get_queried_object();

    if ($post instanceof WP_Post) {
        if (function_exists('rwmb_meta')) {
            $custom_meta_description = trim((string) rwmb_meta('eh_seo_meta_description', [], $post->ID));
            if ($custom_meta_description !== '') {
                return $custom_meta_description;
            }
        }

        if ($post->post_excerpt !== '') {
            return wp_strip_all_tags($post->post_excerpt);
        }

        $page_config = eurohairlab_get_page_content($post->post_name);
        if (is_array($page_config) && !empty($page_config['description'])) {
            return (string) $page_config['description'];
        }

        $content = trim(wp_strip_all_tags((string) $post->post_content));
        if ($content !== '') {
            return wp_trim_words($content, 26, '...');
        }
    }

    if (is_home() || is_archive()) {
        return 'Articles, scalp insights, treatment planning guidance, and recovery education from Eurohairlab.';
    }

    return 'Eurohairlab focuses on scalp-first diagnosis, personalized recovery programs, and measurable hair transformation.';
}

function eurohairlab_get_page_content(?string $slug = null): ?array
{
    $slug = $slug ?: (get_queried_object() instanceof WP_Post ? get_queried_object()->post_name : '');
    if ($slug === 'treatment-programs') {
        $slug = 'treatments';
    }

    $pages = [
        'about' => [
            'eyebrow' => 'About Eurohairlab',
            'title' => 'Scalp-first care built around clinical clarity.',
            'description' => 'Learn how Eurohairlab combines scalp diagnostics, long-horizon treatment planning, and measurable follow-up for stronger hair recovery.',
            'hero_image' => 'hero-bg.webp',
            'hero_alt' => 'Woman with healthy hair resting in soft natural light',
            'sections' => [
                [
                    'type' => 'split',
                    'theme' => 'light',
                    'eyebrow' => 'What we believe',
                    'title' => 'Hair recovery starts by understanding what the scalp is signalling.',
                    'body' => [
                        'Eurohairlab was built around a simple observation: many hair concerns are treated too late, too broadly, and without enough diagnostic context.',
                        'Our approach prioritizes scalp assessment, medical history, and a realistic recovery roadmap so each recommendation has a reason behind it.',
                    ],
                    'list' => ['Scalp and follicle analysis first', 'Clear treatment milestones', 'Education that clients can follow at home'],
                    'media' => ['image' => 'scalp-analysis.webp', 'alt' => 'Close-up scalp diagnosis and treatment consultation'],
                ],
                [
                    'type' => 'cards',
                    'theme' => 'blush',
                    'eyebrow' => 'Care principles',
                    'title' => 'Every recommendation is designed to be understandable, trackable, and sustainable.',
                    'cards' => [
                        ['title' => 'Diagnosis before intervention', 'text' => 'We map symptoms, scalp condition, and possible triggers before discussing any plan.'],
                        ['title' => 'Programs with checkpoints', 'text' => 'Each program is structured around measurable progress instead of vague expectations.'],
                        ['title' => 'Long-term scalp health', 'text' => 'The goal is not a quick cosmetic change but stronger foundations for recovery.'],
                    ],
                ],
                [
                    'type' => 'timeline',
                    'theme' => 'dark',
                    'eyebrow' => 'How we work',
                    'title' => 'A tighter process reduces guesswork.',
                    'steps' => [
                        ['title' => '01. Consultation', 'text' => 'Initial concerns, treatment history, lifestyle triggers, and practical goals are reviewed.'],
                        ['title' => '02. Diagnosis', 'text' => 'Scalp condition, hair density patterns, and treatment suitability are assessed.'],
                        ['title' => '03. Program design', 'text' => 'We combine in-clinic care and at-home guidance into a realistic schedule.'],
                        ['title' => '04. Review and adjust', 'text' => 'Follow-up sessions compare outcomes and refine the plan where needed.'],
                    ],
                ],
            ],
        ],
        'promo' => [
            'eyebrow' => 'Offers & Packages',
            'title' => 'Focused promos that support first visits and program continuity.',
            'description' => 'Explore limited-time Eurohairlab promos for consultation, analysis, and treatment bundles.',
            'hero_image' => 'journey-after.webp',
            'hero_alt' => 'Healthy glossy hair after treatment',
            'sections' => [
                [
                    'type' => 'cards',
                    'theme' => 'light',
                    'eyebrow' => 'Current highlights',
                    'title' => 'Use promotional programs to begin with the right level of care.',
                    'cards' => [
                        ['title' => 'First scalp analysis', 'text' => 'A reduced entry package for new clients who need diagnostic clarity before committing to treatment.'],
                        ['title' => 'Program booster sessions', 'text' => 'Add-on visits for existing clients who need targeted support during recovery.'],
                        ['title' => 'Couple or family consult bundles', 'text' => 'Shared booking windows with pricing structured for coordinated planning.'],
                    ],
                ],
                [
                    'type' => 'split',
                    'theme' => 'sand',
                    'eyebrow' => 'Why promos matter',
                    'title' => 'Promotions should simplify decision-making, not pressure it.',
                    'body' => [
                        'Each offer is framed around a clear outcome: better diagnostics, better continuity, or better value for scheduled treatment blocks.',
                        'That keeps the conversation anchored in suitability rather than price alone.',
                    ],
                    'list' => ['Transparent inclusions', 'No vague package language', 'Suitable for first-time and returning clients'],
                    'media' => ['image' => 'journey-before.webp', 'alt' => 'Three smiling women representing client confidence'],
                ],
                [
                    'type' => 'faq',
                    'theme' => 'white',
                    'eyebrow' => 'Promo FAQ',
                    'title' => 'Common questions before booking.',
                    'items' => [
                        ['question' => 'Can promos be combined?', 'answer' => 'Only when the treatment timeline and consultation notes support it. Combinations should not compromise assessment quality.'],
                        ['question' => 'Do promos apply to existing clients?', 'answer' => 'Some do. Returning clients may qualify for continuity or booster pricing depending on their active program.'],
                        ['question' => 'Are promo slots limited?', 'answer' => 'Yes. Availability depends on therapist schedule and the number of diagnostic sessions allocated each month.'],
                    ],
                ],
            ],
        ],
        'contact' => [
            'eyebrow' => 'Contact Eurohairlab',
            'title' => 'Book a conversation that moves from concern to clear next steps.',
            'description' => 'Contact Eurohairlab for consultation, location details, and treatment planning enquiries.',
            'hero_image' => 'journey-before.webp',
            'hero_alt' => 'Smiling women representing confidence and beauty care',
            'sections' => [
                [
                    'type' => 'contact',
                    'theme' => 'light',
                    'eyebrow' => 'Ways to reach us',
                    'title' => 'Fast contact options for bookings, referrals, and treatment questions.',
                    'details' => [
                        ['label' => 'Email', 'value' => 'hello@eurohairlab.com', 'href' => 'mailto:hello@eurohairlab.com'],
                        ['label' => 'Phone', 'value' => '+62 21 555 0188', 'href' => 'tel:+62215550188'],
                        ['label' => 'Address', 'value' => 'Central Jakarta, Indonesia', 'href' => 'https://maps.google.com/?q=Central+Jakarta+Indonesia'],
                        ['label' => 'Hours', 'value' => 'Mon-Sat, 09:00-18:00', 'href' => ''],
                    ],
                ],
                [
                    'type' => 'form',
                    'theme' => 'sand',
                    'eyebrow' => 'Consultation form',
                    'title' => 'Share the basics and we can route you to the right next step.',
                    'form_intro' => 'This markup is ready for a form plugin or custom handler. The fields are chosen to support SEO-safe, accessible form UX and useful pre-qualification.',
                ],
            ],
        ],
        'diagnosis' => [
            'eyebrow' => 'Scalp Diagnosis',
            'title' => 'Diagnosis that gives treatment a stronger foundation.',
            'description' => 'Understand how Eurohairlab evaluates scalp condition, follicle health, and the likely drivers behind thinning or shedding.',
            'hero_image' => 'scalp-analysis.webp',
            'hero_alt' => 'Specialist performing close scalp analysis',
            'sections' => [
                [
                    'type' => 'split',
                    'theme' => 'light',
                    'eyebrow' => 'Diagnostic lens',
                    'title' => 'A better diagnosis shortens the distance between uncertainty and action.',
                    'body' => [
                        'Scalp care works best when the treatment plan is tied to visible signs, client history, and how the concern has progressed over time.',
                        'That is why our diagnosis phase is not treated as a formality. It is the basis for deciding what to do, what to avoid, and what to monitor.',
                    ],
                    'list' => ['Scalp surface observation', 'Follicle activity review', 'Lifestyle and trigger mapping'],
                    'media' => ['image' => 'scalp-analysis.webp', 'alt' => 'Scalp diagnosis equipment on a clinical table'],
                ],
                [
                    'type' => 'cards',
                    'theme' => 'blush',
                    'eyebrow' => 'What gets evaluated',
                    'title' => 'Diagnosis is structured around the variables that most often change outcomes.',
                    'cards' => [
                        ['title' => 'Density patterns', 'text' => 'We review visible thinning, localized density shifts, and recession trends.'],
                        ['title' => 'Scalp environment', 'text' => 'Oiliness, sensitivity, inflammation, and buildup can change what treatments are appropriate.'],
                        ['title' => 'Growth cycle disruption', 'text' => 'Stress, hormones, illness, and nutritional factors can all influence the plan.'],
                    ],
                ],
                [
                    'type' => 'cta',
                    'theme' => 'dark',
                    'eyebrow' => 'Need diagnostic clarity?',
                    'title' => 'Start with a consultation that is designed to reduce guesswork.',
                    'text' => 'If you are not sure whether the concern is temporary shedding, pattern thinning, or scalp instability, diagnosis is the right first move.',
                    'cta' => ['label' => 'Book Consultation', 'url' => eurohairlab_get_primary_cta_url()],
                ],
            ],
        ],
        'treatments' => [
            'eyebrow' => 'Treatment Programs',
            'title' => 'Programs built to restore scalp health first, then improve hair recovery conditions.',
            'description' => 'Review Eurohairlab treatment programs, session structures, and support layers for sustained scalp and hair recovery.',
            'hero_image' => 'hero-bg.webp',
            'hero_alt' => 'Hair and scalp treatment in a calm clinical environment',
            'sections' => [
                [
                    'type' => 'cards',
                    'theme' => 'light',
                    'eyebrow' => 'Program types',
                    'title' => 'Each program level reflects diagnostic complexity and treatment intensity.',
                    'cards' => [
                        ['title' => 'Reset program', 'text' => 'For clients who need scalp calming, cleansing, and baseline maintenance before advanced interventions.'],
                        ['title' => 'Recovery program', 'text' => 'For ongoing thinning or shedding patterns that need structured in-clinic sessions and home support.'],
                        ['title' => 'Advanced support program', 'text' => 'For clients requiring closer monitoring, adjunctive therapies, and longer review cycles.'],
                    ],
                ],
                [
                    'type' => 'timeline',
                    'theme' => 'sand',
                    'eyebrow' => 'Program rhythm',
                    'title' => 'Consistency matters more than intensity spikes.',
                    'steps' => [
                        ['title' => 'Assessment', 'text' => 'Review suitability and create a treatment calendar.'],
                        ['title' => 'Active phase', 'text' => 'Perform sessions on the agreed cadence and track tolerance.'],
                        ['title' => 'Reassessment', 'text' => 'Compare scalp response, density changes, and next priorities.'],
                        ['title' => 'Maintenance', 'text' => 'Reduce frequency while preserving scalp stability and home-care compliance.'],
                    ],
                ],
                [
                    'type' => 'split',
                    'theme' => 'white',
                    'eyebrow' => 'Included support',
                    'title' => 'Programs are easier to follow when expectations are explicit.',
                    'body' => [
                        'Clients should know what each phase is trying to achieve, how long it may take, and when it makes sense to escalate or pause.',
                        'We structure program communication around those checkpoints so progress can be discussed practically.',
                    ],
                    'list' => ['Treatment notes and recommendations', 'At-home routine guidance', 'Periodic review checkpoints'],
                    'media' => ['image' => 'journey-after.webp', 'alt' => 'Healthy shiny hair representing recovery outcome'],
                ],
            ],
        ],
        'results' => [
            'eyebrow' => 'Results & Recovery',
            'title' => 'Outcomes are strongest when expectations are tied to process and review.',
            'description' => 'See how Eurohairlab frames hair recovery results through case progress, timelines, and treatment adherence.',
            'hero_image' => 'journey-after.webp',
            'hero_alt' => 'Recovered hair texture and density after treatment',
            'sections' => [
                [
                    'type' => 'cards',
                    'theme' => 'light',
                    'eyebrow' => 'What results mean here',
                    'title' => 'Results are evaluated through scalp stability, density change, and response over time.',
                    'cards' => [
                        ['title' => 'Early signals', 'text' => 'Reduced irritation, calmer scalp condition, and better routine adherence often appear first.'],
                        ['title' => 'Visible progress', 'text' => 'Density improvement and reduced shedding are reviewed against baseline observations.'],
                        ['title' => 'Long-term maintenance', 'text' => 'Sustained results depend on program continuity and realistic follow-up intervals.'],
                    ],
                ],
                [
                    'type' => 'metrics',
                    'theme' => 'dark',
                    'eyebrow' => 'Recovery mindset',
                    'title' => 'Meaningful progress usually comes from steady inputs, not one-off interventions.',
                    'items' => [
                        ['value' => '90-day', 'label' => 'Typical review milestone for visible progress'],
                        ['value' => '3 layers', 'label' => 'Diagnosis, treatment cadence, and home routine'],
                        ['value' => '1 plan', 'label' => 'Aligned to scalp condition and lifestyle reality'],
                    ],
                ],
                [
                    'type' => 'faq',
                    'theme' => 'sand',
                    'eyebrow' => 'Results FAQ',
                    'title' => 'A few important expectations to set early.',
                    'items' => [
                        ['question' => 'How fast should I expect change?', 'answer' => 'Recovery timing depends on scalp condition, trigger history, and consistency. Early change may show in scalp comfort before density.'],
                        ['question' => 'Can every case fully recover?', 'answer' => 'Not every concern has the same ceiling. The right goal is improvement matched to diagnosis and response over time.'],
                        ['question' => 'Do I still need home care?', 'answer' => 'Yes. In-clinic treatment works best when the scalp routine outside the clinic supports the same objective.'],
                    ],
                ],
            ],
        ],
    ];

    return $pages[$slug] ?? null;
}
