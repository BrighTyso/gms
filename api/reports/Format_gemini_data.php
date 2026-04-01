<?php

/**
 * Cleans and formats raw agronomic analysis text for management email output.
 */
function formatAgronomicAnalysis(string $rawText): string
{

    //echo $rawText;

    $lines   = explode("\n", $rawText);
    $html    = '';
    $inList  = false;

    foreach ($lines as $line) {
        $line = trim($line);

        if (empty($line)) {
            if ($inList) {
                $html  .= '</ul>';
                $inList = false;
            }
            continue;
        }

        // --- Strip markdown symbols ---
        $line = cleanMarkdown($line);

        // --- Headings (### or ##) ---
        if (preg_match('/^#{1,3}\s+(.+)/', $line, $m)) {
            if ($inList) { $html .= '</ul>'; $inList = false; }
            $html .= formatHeading($m[1]);
            continue;
        }

        // --- Bullet points (* or -) ---
        if (preg_match('/^[-*]\s+(.+)/', $line, $m)) {
            if (!$inList) {
                $html  .= '<ul style="margin: 8px 0 8px 20px; padding: 0;">';
                $inList = true;
            }
            $html .= '<li style="margin-bottom: 6px; line-height: 1.6; color: #333;">'
                   . highlightKeyTerms($m[1])
                   . '</li>';
            continue;
        }

        // --- Regular paragraph ---
        if ($inList) { $html .= '</ul>'; $inList = false; }
        $html .= '<p style="margin: 6px 0; line-height: 1.7; color: #333;">'
               . highlightKeyTerms($line)
               . '</p>';
    }

    if ($inList) {
        $html .= '</ul>';
    }

    return wrapInEmailContainer($html);
}


/**
 * Removes markdown symbols: **, *, #, \_, //, __
 */
function cleanMarkdown(string $text): string
{
    $patterns = [
        '/\*\*(.+?)\*\*/'  => '$1',   // **bold**
        '/\*(.+?)\*/'      => '$1',   // *italic*
        '/__(.+?)__/'      => '$1',   // __underline__
        '/\_(.+?)\_/'      => '$1',   // _italic_
        '/\/\/\s*/'        => '',     // // comments
        '/\\\\/'           => '',     // backslashes
        '/`(.+?)`/'        => '$1',   // `inline code`
    ];

    foreach ($patterns as $pattern => $replacement) {
        $text = preg_replace($pattern, $replacement, $text);
    }

    return trim($text);
}


/**
 * Formats section headings with green agricultural styling.
 */
function formatHeading(string $text): string
{
    $text = cleanMarkdown($text);

    // Numbered headings get a larger treatment
    if (preg_match('/^\d+\./', $text)) {
        return '<h3 style="color: #2e7d32; margin: 20px 0 6px; font-size: 16px;
                            border-bottom: 1px solid #a5d6a7; padding-bottom: 4px;">'
             . $text . '</h3>';
    }

    return '<h4 style="color: #1b5e20; margin: 14px 0 4px; font-size: 14px;">'
         . $text . '</h4>';
}


/**
 * Highlights key agronomic terms for management readability.
 */
function highlightKeyTerms(string $text): string
{
    $atRiskTerms = [
        'at risk', 'missing', 'null', 'placeholder', 'inconsistent',
        'insufficient', 'limited', 'problematic', 'issue', 'missing values'
    ];

    $positiveTerms = [
        'recommendation', 'action', 'expected outcome', 'improved', 'accurate'
    ];

    foreach ($atRiskTerms as $term) {
        $text = preg_replace(
            '/\b(' . preg_quote($term, '/') . ')\b/i',
            '<span style="color: #c62828; font-weight: bold;">$1</span>',
            $text
        );
    }

    foreach ($positiveTerms as $term) {
        $text = preg_replace(
            '/\b(' . preg_quote($term, '/') . ')\b/i',
            '<span style="color: #2e7d32; font-weight: bold;">$1</span>',
            $text
        );
    }

    return $text;
}


/**
 * Wraps the formatted content in a clean email-safe container.
 */
function wrapInEmailContainer(string $content): string
{
    return '
    <div style="font-family: Arial, sans-serif; max-width: 780px; margin: 0 auto;
                background: #ffffff; border: 1px solid #dcedc8; border-radius: 6px;
                padding: 24px;">

        <div style="background: #2e7d32; padding: 14px 20px; border-radius: 4px; margin-bottom: 20px;">
            <h2 style="color: #ffffff; margin: 0; font-size: 18px; letter-spacing: 0.5px;">
                &#127807; Agronomic Analysis Report
            </h2>
            <p style="color: #c8e6c9; margin: 4px 0 0; font-size: 12px;">
                Generated: ' . date('d M Y, H:i') . '
            </p>
        </div>

        <div style="padding: 0 4px;">
            ' . $content . '
        </div>

        <div style="border-top: 1px solid #dcedc8; margin-top: 20px; padding-top: 10px;
                    font-size: 11px; color: #888; text-align: center;">
            This report was generated automatically. Contact your agronomist for further guidance.
        </div>
    </div>';
}


// -----------------------------------------------------------------------
// USAGE — pass your raw analysis text as a string
// -----------------------------------------------------------------------

// $rawAnalysis = <<<TEXT
// ### 1. High-Level Summary of Performance

// Based on the data, it's difficult to give a comprehensive performance overview due to missing values, generic entries,
// and lack of comparative data. Here's what *can* be observed:

// * **Inconsistent Data Entry:** A significant portion of the data is populated with placeholder values like "surname",
// "name", and "grower_num". This makes it difficult to draw meaningful conclusions.
// * **Limited Crop Measurement Data:** For most growers, critical crop measurements (age, plant height, vigor, density)
// are either missing (null) or represented by empty strings.
// * **Pest and Disease Management:** Pest and disease are observed, with treatments in place.
// * **Soil Moisture:** Only one useful row is present; the state of field irrigation should be considered.

// ### 2. Growers at Risk of Low Yield

// Given the limited and often placeholder data, definitively identifying growers at risk is problematic. However:

// * **Grower "VHELLONEW":** While data exists for this grower, the lack of plant height, vigor, and density, along with a
// crop age of 0, indicates either very newly planted crops, a significant issue with establishment, or data entry problems.

// ### 3. One Actionable Recommendation for Next Week

// **Recommendation:** Prioritize data validation and training for data entry personnel. The current data quality is
// insufficient for effective decision-making.

// * **Specific Action:** Implement data validation rules in the system to prevent the entry of generic placeholder values.
// * **Rationale:** Accurate data is the foundation of sound agronomic decisions.
// * **Expected Outcome:** Improved data quality leading to better insights into crop health and resource allocation.
// TEXT;

// $formattedEmail = formatAgronomicAnalysis($rawAnalysis);

// Use with PHPMailer:
// $mail->isHTML(true);
// $mail->Subject = 'Agronomic Analysis Report - ' . date('d M Y');
// $mail->Body    = $formattedEmail;
// $mail->AltBody = strip_tags($formattedEmail);

//echo $formattedEmail;