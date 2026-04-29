<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Default Pre-Consultation copy per existing template masking_id (Reports 1–8).
 *
 * @return array<string, array<string, string>>
 */
function eh_assessment_precon_report_pdf_template_seed_rows(): array
{
    $greeting = <<<'HTML'
<p>Terima kasih telah mengisi EUROHAIRLAB by Dr. Scalp Online Hair Diagnosis. Berikut adalah hasil evaluasi awal kondisi rambut dan kulit kepala Anda.</p>
HTML;

    $journey = <<<'HTML'
<p>Setiap program treatment di EUROHAIRLAB by Dr. Scalp mengikuti sistem SCALPFIRST™ System. Framework klinis proprietary yang memastikan setiap terapi bekerja pada potensi tertingginya. Hair Specialist kami akan menjelaskan journey ini secara personal saat konsultasi.</p>
HTML;

    $medical = <<<'HTML'
<p>Dokumen ini adalah Pre-Consultation Report yang disusun berdasarkan jawaban kuesioner online, bukan diagnosis medis.</p>
<p>Kondisi rambut dan kulit kepala yang sesungguhnya hanya dapat ditentukan melalui pemeriksaan langsung oleh dokter EUROHAIRLAB by Dr. Scalp menggunakan Scalp Analyzer dan analisis riwayat medis lengkap. Hasil konsultasi klinis dapat berbeda dari gambaran dalam dokumen ini.</p>
HTML;

    $common = [
        'report_header_title' => 'HAIR HEALTH',
        'subtitle' => 'PRE-CONSULTATION REPORT',
        'greeting_description' => $greeting,
        'title_condition_explanation' => 'PENJELASAN KONDISI',
        'title_clinical_knowledge' => 'WAWASAN KLINIS',
        'title_evaluation_urgency' => 'PENILAIAN URGENSI',
        'title_treatment_journey' => 'YOUR SCALPFIRST™ TREATMENT JOURNEY',
        'description_treatment_journey' => $journey,
        'title_recommendation_approach' => 'REKOMENDASI PENDEKATAN',
        'title_next_steps' => 'LANGKAH SELANJUTNYA',
        'title_medical_notes' => 'CATATAN MEDIS',
        'body_medical_notes' => $medical,
        'description_medical_notes' => '',
        'image_clinical_knowledge' => '',
        'image_treatment_journey' => '',
    ];

    return [
        'RPT-2604-EI8NGTDQ' => array_merge($common, [
            'diagnosis_name' => 'Early Stage Hair Thinning',
            'diagnosis_name_detail' => 'Hair Loss Aktif',
            'description_condition_explanation' => <<<'HTML'
<p>Rambut Anda menunjukkan tanda-tanda penipisan pada tahap awal. Kondisi ini umumnya terjadi ketika siklus pertumbuhan rambut mulai mengalami perubahan. Rambut yang tumbuh menjadi lebih sedikit dan lebih halus dari sebelumnya.</p>
<p>Pada fase ini, sebagian besar folikel rambut masih aktif namun mulai melemah akibat kombinasi faktor seperti hormonal, stres, atau gaya hidup. Ini adalah fase dengan peluang terbaik untuk intervensi.</p>
<p>Kondisi ini masih berada dalam window of opportunity terluas, di mana respons terhadap treatment paling optimal.</p>
HTML,
            'subtitle_clinical_knowledge' => 'FASE PERTUMBUHAN RAMBUT',
            'description_clinical_knowledge' => <<<'HTML'
<p>Memahami siklus pertumbuhan rambut membantu menjelaskan mengapa intervensi dini memberikan hasil terbaik. Kondisi Anda saat ini berada di fase paling responsif terhadap treatment.</p>
HTML,
            'description_evaluation_urgency' => <<<'HTML'
<p>Kondisi ini masih berada dalam fase optimal untuk ditangani. Semakin cepat dilakukan evaluasi klinis, semakin besar peluang untuk mempertahankan dan meningkatkan pertumbuhan rambut.</p>
HTML,
            'description_recommendation_approach' => '<p>Pendekatan yang umumnya digunakan untuk kondisi ini:</p>',
            'detail_recommendation_approach' => <<<'HTML'
<ul>
<li>Penguatan dan stimulasi folikel rambut</li>
<li>Optimasi kondisi kulit kepala</li>
<li>Pencegahan progresi kondisi</li>
</ul>
HTML,
            'bottom_description_recommendation_approach' => <<<'HTML'
<p>Pendekatan spesifik akan ditentukan oleh dokter berdasarkan hasil trichoscopy dan analisis kondisi folikel Anda secara langsung.</p>
HTML,
            'description_next_steps' => <<<'HTML'
<p>Jadwalkan konsultasi klinis Anda di EUROHAIRLAB by Dr. Scalp. Tim dokter kami siap melakukan pemeriksaan langsung untuk menentukan program yang paling tepat bagi kondisi spesifik Anda.</p>
HTML,
        ]),
        'RPT-2604-BJGDY41C' => array_merge($common, [
            'diagnosis_name' => 'Genetic Hair Loss',
            'diagnosis_name_detail' => 'Pola kerontokan genetik',
            'description_condition_explanation' => <<<'HTML'
<p>Hair loss genetik berkembang bertahap dan cenderung memburuk tanpa penanganan yang tepat. Kondisi ini dapat dikelola secara efektif. Progresinya dapat diperlambat secara signifikan dengan protokol yang konsisten.</p>
<p>Semakin dini kondisi ini ditangani, semakin besar yang bisa dipertahankan.</p>
HTML,
            'subtitle_clinical_knowledge' => 'PEMETAAN POLA & SKALA PROGRESI',
            'description_clinical_knowledge' => <<<'HTML'
<p>Skala berikut menunjukkan progresi kondisi genetik dari waktu ke waktu jika tidak ditangani. Penentuan tahap aktual Anda dilakukan melalui trichoscopy langsung di klinik.</p>
<p>Perkembangan kerontokan rambut akibat faktor genetik mengikuti pola yang diakui secara klinis: Norwood-Hamilton Scale (pola kerontokan rambut pria) dan Ludwig-Savin Scale (pola kerontokan rambut wanita).</p>
HTML,
            'description_evaluation_urgency' => <<<'HTML'
<p>Semakin dini ditangani, semakin besar peluang untuk memperlambat proses penipisan dan mempertahankan kepadatan rambut yang ada.</p>
HTML,
            'description_recommendation_approach' => <<<'HTML'
<p>Protokol spesifik akan dirancang dokter berdasarkan profil genetik, hormonal, dan tingkat miniaturisasi folikel Anda yang hanya dapat diukur melalui pemeriksaan langsung.</p>
<p>Pendekatan yang umumnya digunakan untuk kondisi ini:</p>
HTML,
            'detail_recommendation_approach' => <<<'HTML'
<ul>
<li>Manajemen faktor genetik dan hormonal</li>
<li>Stimulasi folikel yang masih aktif</li>
<li>Optimasi kondisi kulit kepala jangka panjang</li>
</ul>
HTML,
            'bottom_description_recommendation_approach' => '',
            'description_next_steps' => <<<'HTML'
<p>Jadwalkan konsultasi klinis Anda di EUROHAIRLAB by Dr. Scalp. Tim dokter kami siap merancang program jangka panjang yang disesuaikan dengan profil genetik dan hormonal Anda secara akurat.</p>
HTML,
        ]),
        'RPT-2604-JSG53HZX' => array_merge($common, [
            'diagnosis_name' => 'Progressive Hair Loss',
            'diagnosis_name_detail' => 'Kerontokan progresif',
            'description_condition_explanation' => <<<'HTML'
<p>Rambut Anda mengalami penipisan yang berlangsung secara bertahap dan berkelanjutan. Hal ini menunjukkan adanya perubahan pada siklus pertumbuhan rambut, di mana folikel secara progresif menghasilkan rambut yang lebih tipis dari sebelumnya.</p>
<p>Pada tahap ini, sebagian folikel masih dapat dioptimalkan, namun sebagian lainnya mulai mengalami miniaturisasi yang memerlukan pendekatan regeneratif aktif.</p>
<p>Penanganan lebih awal dapat membantu memperlambat progresi dan mempertahankan kepadatan rambut yang masih ada.</p>
HTML,
            'subtitle_clinical_knowledge' => 'MINIATURISASI FOLIKEL',
            'description_clinical_knowledge' => <<<'HTML'
<p>Miniaturisasi folikel adalah proses bertahap yang dapat diperlambat dengan intervensi yang tepat. Tingkat miniaturisasi aktual Anda hanya dapat diukur melalui trichoscopy langsung.</p>
HTML,
            'description_evaluation_urgency' => <<<'HTML'
<p>Penanganan lebih awal dapat membantu memutus akselerasi miniaturisasi dan mempertahankan folikel yang masih viable.</p>
HTML,
            'description_recommendation_approach' => <<<'HTML'
<p>Evaluasi trichoscopy untuk mengukur tingkat miniaturisasi sangat penting sebelum protokol treatment dirancang. Dokter kami akan menentukan pendekatan yang paling tepat berdasarkan kondisi aktual folikel Anda.</p>
<p>Pendekatan yang umumnya digunakan untuk kondisi ini:</p>
HTML,
            'detail_recommendation_approach' => <<<'HTML'
<ul>
<li>Aktivasi folikel yang melemah</li>
<li>Memperlambat progresi miniaturisasi</li>
<li>Peningkatan kepadatan rambut residual</li>
</ul>
HTML,
            'bottom_description_recommendation_approach' => '',
            'description_next_steps' => <<<'HTML'
<p>Jadwalkan konsultasi klinis Anda di EUROHAIRLAB by Dr. Scalp. Tim dokter kami akan melakukan trichoscopy langsung untuk merancang protokol yang paling efektif bagi kondisi Anda.</p>
HTML,
        ]),
        'RPT-2604-VVFZIE6O' => array_merge($common, [
            'diagnosis_name' => 'Stress / Lifestyle Hair Loss',
            'diagnosis_name_detail' => 'Terkait stres & gaya hidup',
            'description_condition_explanation' => <<<'HTML'
<p>Kerontokan rambut Anda kemungkinan dipicu oleh faktor stres, kurang tidur, atau perubahan gaya hidup. Kondisi ini mendorong folikel masuk ke fase istirahat lebih cepat, sehingga jumlah rambut yang rontok meningkat secara signifikan.</p>
<p>Kondisi ini sering muncul 6–12 minggu setelah pemicu terjadi, sehingga sering terasa datang tiba-tiba padahal prosesnya sudah berlangsung sebelumnya.</p>
<p>Kabar baiknya: ini adalah kategori hair loss yang paling responsif terhadap treatment jika ditangani dengan pendekatan yang tepat.</p>
HTML,
            'subtitle_clinical_knowledge' => 'SIKLUS FOLIKEL RAMBUT',
            'description_clinical_knowledge' => <<<'HTML'
<p>Stres mendorong folikel masuk ke fase telogen secara paksa dan massal. Folikel yang masuk ke fase ini tidak mati, mereka dormant dan dapat diaktifkan kembali.</p>
HTML,
            'description_evaluation_urgency' => <<<'HTML'
<p>Kondisi ini termasuk yang paling dapat dipulihkan. Dengan intervensi yang tepat, siklus folikel dapat di-reset dan pertumbuhan baru dapat diaktifkan kembali.</p>
HTML,
            'description_recommendation_approach' => <<<'HTML'
<p>Dokter kami akan mengevaluasi faktor pemicu secara spesifik untuk merancang protokol yang paling efektif. Pendekatan disesuaikan dengan profil individual Anda.</p>
<p>Pendekatan yang umumnya digunakan untuk kondisi ini:</p>
HTML,
            'detail_recommendation_approach' => <<<'HTML'
<ul>
<li>Reset siklus pertumbuhan rambut</li>
<li>Normalisasi kondisi kulit kepala</li>
<li>Reaktivasi folikel yang dormant</li>
</ul>
HTML,
            'bottom_description_recommendation_approach' => '',
            'description_next_steps' => <<<'HTML'
<p>Jadwalkan konsultasi klinis Anda di EUROHAIRLAB by Dr. Scalp. Hair Specialist kami akan merancang protokol yang disesuaikan dengan faktor pemicu spesifik kondisi Anda.</p>
HTML,
        ]),
        'RPT-2604-SSKCEBYA' => array_merge($common, [
            'diagnosis_name' => 'Scalp Only',
            'diagnosis_name_detail' => 'Fokus kesehatan kulit kepala',
            'description_condition_explanation' => <<<'HTML'
<p>Kulit kepala Anda menunjukkan tanda-tanda ketidakseimbangan yang dapat mempengaruhi pertumbuhan rambut. Kondisi scalp yang tidak optimal menciptakan lingkungan yang kurang ideal bagi folikel, bahkan sebelum hair loss aktif terjadi.</p>
<p>Scalp memiliki kelenjar sebaceous dengan densitas jauh lebih tinggi dibandingkan bagian kulit lainnya, menjadikannya sangat rentan terhadap ketidakseimbangan seperti sebum berlebih, inflamasi, atau sensitivitas.</p>
<p>Kondisi scalp adalah masalah yang dapat dikoreksi dengan pendekatan yang tepat.</p>
HTML,
            'subtitle_clinical_knowledge' => 'STRUKTUR RAMBUT &amp; KULIT',
            'description_clinical_knowledge' => <<<'HTML'
<p>Folikel rambut tertanam dalam struktur kulit yang kompleks. Kondisi scalp yang tidak optimal langsung menghambat nutrisi folikel dan menciptakan lingkungan yang mendukung inflamasi.</p>
HTML,
            'description_evaluation_urgency' => <<<'HTML'
<p>Kondisi scalp yang tidak ditangani dapat berkembang menjadi masalah rambut jangka panjang. Intervensi dini mencegah progresi ke tahap yang lebih serius.</p>
HTML,
            'description_recommendation_approach' => <<<'HTML'
<p>Penanganan kondisi scalp memerlukan identifikasi jenis kondisi yang tepat melalui analisis langsung. Dokter kami akan menentukan treatment yang paling sesuai berdasarkan pemeriksaan aktual.</p>
<p>Pendekatan yang umumnya digunakan untuk kondisi ini:</p>
HTML,
            'detail_recommendation_approach' => <<<'HTML'
<ul>
<li>Penyeimbangan kondisi kulit kepala</li>
<li>Pengurangan inflamasi dan iritasi</li>
<li>Dukungan lingkungan folikel yang optimal</li>
</ul>
HTML,
            'bottom_description_recommendation_approach' => '',
            'description_next_steps' => <<<'HTML'
<p>Jadwalkan konsultasi klinis Anda di EUROHAIRLAB by Dr. Scalp. Tim dokter kami akan melakukan analisis scalp langsung untuk menentukan treatment SCALPFIRST™ yang paling sesuai dengan kondisi Anda.</p>
HTML,
        ]),
        'RPT-2604-7YWCOCJZ' => array_merge($common, [
            'diagnosis_name' => 'Multi-Factor Hair Loss',
            'diagnosis_name_detail' => 'Multi-faktor',
            'description_condition_explanation' => <<<'HTML'
<p>Kerontokan rambut Anda kemungkinan dipengaruhi oleh beberapa faktor yang bekerja secara bersamaan. Kondisi seperti ini memerlukan pendekatan yang lebih menyeluruh dibandingkan hair loss dengan faktor tunggal.</p>
<p>Ketika multiple faktor aktif secara bersamaan, dampaknya tidak hanya menjumlahkan. Mereka saling memperkuat satu sama lain. Inilah mengapa pendekatan tunggal hampir tidak pernah memberikan hasil optimal untuk profil seperti ini.</p>
<p>Pendekatan kombinasi yang terstruktur dan sistematis adalah kunci untuk kondisi multi-faktor.</p>
HTML,
            'subtitle_clinical_knowledge' => 'MULTI-FACTOR HAIR LOSS',
            'description_clinical_knowledge' => <<<'HTML'
<p>Pada profil multi-faktor, tidak cukup hanya menangani satu pemicu saja. Evaluasi klinis menyeluruh membantu dokter memetakan kombinasi faktor yang saling memperkuat, lalu merancang prioritas intervensi.</p>
HTML,
            'description_evaluation_urgency' => <<<'HTML'
<p>Pendekatan yang tepat sejak awal sangat penting. Kondisi multi-faktor yang tidak ditangani secara komprehensif cenderung semakin kompleks seiring waktu.</p>
HTML,
            'description_recommendation_approach' => <<<'HTML'
<p>Untuk profil multi-faktor, dokter kami akan merancang protokol kombinasi yang komprehensif berdasarkan evaluasi menyeluruh kondisi aktual Anda.</p>
<p>Pendekatan yang umumnya digunakan untuk kondisi ini:</p>
HTML,
            'detail_recommendation_approach' => <<<'HTML'
<ul>
<li>Pendekatan kombinasi untuk multiple faktor</li>
<li>Penanganan setiap faktor secara sistematis</li>
<li>Optimasi hasil melalui protokol terstruktur</li>
</ul>
HTML,
            'bottom_description_recommendation_approach' => '',
            'description_next_steps' => <<<'HTML'
<p>Jadwalkan konsultasi klinis Anda di EUROHAIRLAB by Dr. Scalp. Tim dokter kami akan merancang protokol kombinasi yang komprehensif, disesuaikan dengan semua faktor yang mempengaruhi kondisi rambut Anda.</p>
HTML,
        ]),
        'RPT-2604-FLIKQ4ZF' => array_merge($common, [
            'diagnosis_name' => 'Advanced Hair Loss',
            'diagnosis_name_detail' => 'Kerontokan lanjut',
            'description_condition_explanation' => <<<'HTML'
<p>Kerontokan rambut Anda sudah berlangsung cukup lama dengan penurunan kepadatan yang signifikan. Pada tahap ini, menentukan kondisi folikel secara akurat adalah langkah kritis sebelum treatment apapun dimulai.</p>
<p>Trichoscopy memungkinkan dokter melihat kondisi folikel pada tingkat detail: mengidentifikasi folikel yang masih aktif, yang dormant, dan yang memerlukan pendekatan khusus.</p>
<p>EUROHAIRLAB akan memberikan gambaran yang jujur dan realistis berdasarkan kondisi aktual yang ditemukan pada pemeriksaan langsung.</p>
HTML,
            'subtitle_clinical_knowledge' => 'PERKEMBANGAN ANDROGENETIC ALOPECIA (AGA)',
            'description_clinical_knowledge' => <<<'HTML'
<p>Diagram ini menunjukkan bagaimana AGA secara bertahap mengubah folikel sehat. Memahami mekanisme ini adalah dasar dari setiap keputusan treatment klinis di EUROHAIRLAB.</p>
HTML,
            'description_evaluation_urgency' => <<<'HTML'
<p>Evaluasi yang akurat menjadi langkah utama sebelum menentukan treatment. Trichoscopy langsung adalah satu-satunya cara untuk mengetahui kondisi folikel secara akurat.</p>
HTML,
            'description_recommendation_approach' => <<<'HTML'
<p>Dokter kami akan memberikan gambaran klinis yang jujur berdasarkan kondisi aktual folikel Anda termasuk mendiskusikan semua opsi yang tersedia dan memberikan rekomendasi yang realistis.</p>
<p>Pendekatan yang umumnya digunakan untuk kondisi ini:</p>
HTML,
            'detail_recommendation_approach' => <<<'HTML'
<ul>
<li>Evaluasi viabilitas folikel yang akurat</li>
<li>Optimasi folikel yang masih dapat diaktifkan</li>
<li>Penentuan solusi klinis yang realistis</li>
</ul>
HTML,
            'bottom_description_recommendation_approach' => '',
            'description_next_steps' => <<<'HTML'
<p>Jadwalkan konsultasi klinis Anda di EUROHAIRLAB by Dr. Scalp. Tim dokter kami akan melakukan pemeriksaan trichoscopy menyeluruh dan memberikan gambaran klinis yang akurat tentang semua opsi yang tersedia untuk kondisi Anda.</p>
HTML,
        ]),
        'RPT-2604-M9ATNWOJ' => array_merge($common, [
            'diagnosis_name' => 'Optimasi — DEFAULT',
            'diagnosis_name_detail' => 'Profil perawatan preventif',
            'description_condition_explanation' => <<<'HTML'
<p>Kondisi rambut dan kulit kepala Anda saat ini berada dalam rentang yang baik. Ini adalah waktu yang tepat untuk membangun fondasi perawatan yang akan menjaga kualitas rambut Anda dalam jangka panjang.</p>
<p>Rambut yang sehat hari ini tidak akan bertahan begitu saja tanpa perawatan yang tepat. Perubahan hormonal, akumulasi stres, dan faktor lingkungan bekerja diam-diam sebelum dampaknya terlihat.</p>
<p>Perawatan preventif yang dimulai sekarang adalah investasi paling efektif yang bisa Anda lakukan untuk kesehatan rambut jangka panjang.</p>
HTML,
            'subtitle_clinical_knowledge' => 'PENCEGAHAN KERONTOKAN RAMBUT',
            'description_clinical_knowledge' => <<<'HTML'
<p>Kebiasaan sehari-hari memiliki dampak signifikan terhadap kesehatan rambut jangka panjang. Perawatan preventif yang konsisten adalah investasi terbaik untuk kondisi rambut Anda di masa depan.</p>
HTML,
            'description_evaluation_urgency' => <<<'HTML'
<p>Menjaga kondisi yang sudah baik jauh lebih mudah dan lebih ekonomis dibandingkan memulihkannya setelah masalah berkembang.</p>
HTML,
            'description_recommendation_approach' => <<<'HTML'
<p>Pendekatan yang umumnya digunakan untuk kondisi ini:</p>
HTML,
            'detail_recommendation_approach' => <<<'HTML'
<ul>
<li>Perawatan preventif scalp secara berkala</li>
<li>Menjaga keseimbangan kondisi kulit kepala</li>
<li>Mempertahankan kualitas rambut jangka panjang</li>
</ul>
HTML,
            'bottom_description_recommendation_approach' => <<<'HTML'
<p>Korean Scalp Ritual dirancang untuk mereka yang ingin merawat rambut secara proaktif. Program dapat disesuaikan dengan kondisi dan kebutuhan spesifik Anda.</p>
HTML,
            'description_next_steps' => <<<'HTML'
<p>Jadwalkan konsultasi klinis Anda di EUROHAIRLAB by Dr. Scalp. Program perawatan preventif eksklusif yang dirancang untuk menjaga kualitas rambut jangka panjang sebelum masalah berkembang.</p>
HTML,
        ]),
    ];
}
