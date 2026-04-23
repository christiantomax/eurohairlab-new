<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Map English assessment copy (theme defaults) to Indonesian for Cekat webhook `answers` only.
 * Unknown strings are left unchanged (e.g. CMS already in Indonesian).
 *
 * @return array<string, string>
 */
function eh_assessment_cekat_webhook_id_label_map(): array
{
    return [
        // Question titles (defaults from theme)
        'Which hair or scalp change are you noticing the most?' => 'Perubahan rambut atau kulit kepala mana yang paling Anda rasakan?',
        'What is the biggest impact you are feeling?' => 'Dampak terbesar apa yang Anda rasakan?',
        'How long have you noticed this change?' => 'Sudah berapa lama Anda menyadari perubahan ini?',
        'Is there a family history of a similar condition?' => 'Apakah ada riwayat keluarga dengan kondisi serupa?',
        'What have you tried so far?' => 'Apa yang sudah Anda coba sejauh ini?',
        'Are you currently experiencing any of the following factors?' => 'Apakah Anda sedang mengalami salah satu faktor berikut?',
        'If left untreated, what worries you the most?' => 'Jika tidak ditangani, apa yang paling Anda khawatirkan?',
        'Have you had a consultation before?' => 'Apakah Anda pernah berkonsultasi sebelumnya?',
        'If your condition improves, what result are you hoping for?' => 'Jika kondisi Anda membaik, hasil apa yang Anda harapkan?',
        'Your Details' => 'Data Anda',

        // Q1
        'Hairline Receding' => 'Garis rambut mundur',
        'Crown Thinning' => 'Penipisan mahkota',
        'Diffuse Thinning' => 'Penipisan merata',
        'Excessive Hair Fall' => 'Rontok berlebihan',
        'Scalp Concerns (Dandruff, Itchiness, Oily Scalp)' => 'Masalah kulit kepala (ketombe, gatal, berminyak)',
        'I Want to Maintain Healthy Hair' => 'Ingin menjaga kesehatan rambut',

        // Q2
        'Lower Confidence' => 'Kepercayaan diri menurun',
        'Worried It Will Get Worse' => 'Khawatir kondisi memburuk',
        'Looking Older' => 'Terlihat lebih tua',
        'Stressed About My Hair' => 'Stres terkait rambut',
        'I Want to Maintain My Current Condition' => 'Ingin mempertahankan kondisi saat ini',

        // Q3
        'Less Than 3 Months' => 'Kurang dari 3 bulan',
        '3 to 6 Months' => '3–6 bulan',
        '6 to 12 Months' => '6–12 bulan',
        'More Than 1 Year' => 'Lebih dari 1 tahun',
        'Not Sure' => 'Tidak yakin',

        // Q4
        'Yes' => 'Ya',
        'No' => 'Tidak',

        // Q5
        'Nothing Yet' => 'Belum pernah',
        'Products / Serums' => 'Produk / serum',
        'Clinic Treatments' => 'Perawatan di klinik',
        'Doctor-Prescribed Medication' => 'Obat resep dokter',
        'A Combination of Methods' => 'Kombinasi beberapa metode',

        // Q6
        'Prolonged Stress' => 'Stres berkepanjangan',
        'Lack of Sleep' => 'Kurang tidur',
        'Dieting / Weight Loss' => 'Diet / penurunan berat badan',
        'Hormonal Changes' => 'Perubahan hormonal',
        'None / Not Sure' => 'Tidak ada / tidak yakin',

        // Q7
        'The Thinning Area Will Spread' => 'Area penipisan akan meluas',
        'I May Need a Transplant' => 'Mungkin perlu transplantasi rambut',
        'I Will Look Older' => 'Akan terlihat lebih tua',
        'I Will Lose Confidence' => 'Kepercayaan diri akan berkurang',
        'I Haven\'t Thought About It Yet' => 'Belum memikirkannya',

        // Q8
        'Never' => 'Belum pernah',
        'Aesthetic Clinic' => 'Klinik estetik',
        'Dermatologist' => 'Dokter kulit',
        'Hair Transplant Consultation' => 'Konsultasi transplantasi rambut',
        'Multiple Approaches' => 'Berbagai pendekatan',

        // Q9
        'Looking Younger' => 'Terlihat lebih muda',
        'Feeling More Confident' => 'Lebih percaya diri',
        'Less Stress About My Hair' => 'Kurang stres tentang rambut',
        'Thicker and Healthier Hair' => 'Rambut lebih tebal dan sehat',
    ];
}

/**
 * @param array<string, array{question?: string, answer?: string}> $answers
 * @return array<string, array{question: string, answer: string}>
 */
function eh_assessment_cekat_webhook_localize_answers_id(array $answers): array
{
    $map = eh_assessment_cekat_webhook_id_label_map();
    $out = [];

    foreach ($answers as $key => $block) {
        if (!is_array($block)) {
            continue;
        }
        $q = trim((string) ($block['question'] ?? ''));
        $a = trim((string) ($block['answer'] ?? ''));
        $out[$key] = [
            'question' => $map[$q] ?? $q,
            'answer' => $map[$a] ?? $a,
        ];
    }

    return $out;
}
