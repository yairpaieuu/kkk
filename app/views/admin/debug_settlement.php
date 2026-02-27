<?php
// FILE: debug_settlement.php
// Usage: /debug_settlement.php?ticket_id=12345

require_once __DIR__ . '/../../app/Core/Database.php'; // Adjust path to your Database file

$ticketId = $_GET['ticket_id'] ?? null;

if (!$ticketId) {
    die("<h3>Please provide a ticket ID in the URL. <br>Example: ?ticket_id=105</h3>");
}

$db = new Database();

// 1. Fetch the Bet and Match Details
$sql = "SELECT b.*, bd.selection, bd.hcap_val, bd.odds, bd.match_id, 
               m.home_team, m.away_team, m.home_score, m.away_score, m.status as match_status
        FROM bets b
        JOIN bet_details bd ON b.id = bd.bet_id
        JOIN matches m ON bd.match_id = m.id
        WHERE b.id = :id";

$db->query($sql);
$db->bind(':id', $ticketId);
$ticket = $db->single();

if (!$ticket) {
    die("Ticket #$ticketId not found.");
}

// 2. Prepare Variables
$selection = strtolower($ticket->selection); // home, away, over, under
$hdp       = floatval($ticket->hcap_val);    // The handicap line (e.g., -0.5, +1.0)
$homeScore = intval($ticket->home_score);
$awayScore = intval($ticket->away_score);
$stake     = floatval($ticket->total_stake);
$odds      = floatval($ticket->total_odds);

echo "<div style='font-family: monospace; padding: 20px; background: #f3f4f6; border: 1px solid #ccc;'>";
echo "<h2 style='margin-top:0'>🕵️ Settlement Debugger: Ticket #{$ticket->id}</h2>";

// --- SECTION A: RAW DATA ---
echo "<h3>1. Raw Data (From DB)</h3>";
echo "<ul>";
echo "<li><strong>Match:</strong> {$ticket->home_team} vs {$ticket->away_team}</li>";
echo "<li><strong>Match Status:</strong> {$ticket->match_status}</li>";
echo "<li><strong>Final Score:</strong> <span style='background:black; color:white; padding:2px 5px;'> $homeScore - $awayScore </span></li>";
echo "<li><strong>User Pick:</strong> " . strtoupper($selection) . "</li>";
echo "<li><strong>Handicap (HDP):</strong> $hdp</li>";
echo "<li><strong>Odds:</strong> $odds</li>";
echo "<li><strong>Stake:</strong> " . number_format($stake) . "</li>";
echo "</ul>";

// --- SECTION B: LOGIC TRACE ---
echo "<h3>2. Calculation Trace</h3>";

$result = 'PENDING';
$percentage = 0; // 0 = Lost, 100 = Win, 50 = Half Win, -50 = Half Lost

// LOGIC FOR HDP (Asian Handicap)
if ($selection == 'home' || $selection == 'away') {
    
    // Apply Handicap to Home Score
    // Note: Usually in DB, HDP is stored relative to Home. 
    // If User picked Away, we might need to invert logic depending on how you stored it.
    // Assuming 'hcap_val' in DB is always Home's handicap (e.g. -0.5 means Home starts -0.5).
    
    $effectiveHomeScore = $homeScore + $hdp;
    $effectiveAwayScore = $awayScore;

    echo "<p><strong>Applying Handicap:</strong><br>";
    echo "Home Score ($homeScore) + HDP ($hdp) = <strong>$effectiveHomeScore</strong><br>";
    echo "Away Score = <strong>$effectiveAwayScore</strong></p>";

    $diff = $effectiveHomeScore - $effectiveAwayScore;
    
    echo "<p><strong>Difference (Home - Away):</strong> $diff</p>";

    if ($selection == 'home') {
        if ($diff > 0.25)      { $result = 'WON'; $percentage = 100; } // Won by 0.5 or more
        elseif ($diff == 0.25) { $result = 'HALF_WON'; $percentage = 50; }
        elseif ($diff == 0)    { $result = 'PUSH'; $percentage = 0; } // Draw exactly
        elseif ($diff == -0.25){ $result = 'HALF_LOST'; $percentage = -50; }
        else                   { $result = 'LOST'; $percentage = -100; }
    } 
    elseif ($selection == 'away') {
        // For Away to win, Home must lose (Diff < 0)
        if ($diff < -0.25)     { $result = 'WON'; $percentage = 100; }
        elseif ($diff == -0.25){ $result = 'HALF_WON'; $percentage = 50; }
        elseif ($diff == 0)    { $result = 'PUSH'; $percentage = 0; }
        elseif ($diff == 0.25) { $result = 'HALF_LOST'; $percentage = -50; }
        else                   { $result = 'LOST'; $percentage = -100; }
    }
} 
// LOGIC FOR OVER/UNDER
elseif ($selection == 'over' || $selection == 'under') {
    $totalGoals = $homeScore + $awayScore;
    echo "<p><strong>Total Goals:</strong> $totalGoals <br> <strong>Line:</strong> $hdp</p>";
    
    $diff = $totalGoals - $hdp;

    if ($selection == 'over') {
        if ($diff > 0.25)      { $result = 'WON'; $percentage = 100; }
        elseif ($diff == 0.25) { $result = 'HALF_WON'; $percentage = 50; }
        elseif ($diff == 0)    { $result = 'PUSH'; $percentage = 0; }
        elseif ($diff == -0.25){ $result = 'HALF_LOST'; $percentage = -50; }
        else                   { $result = 'LOST'; $percentage = -100; }
    } 
    elseif ($selection == 'under') {
        if ($diff < -0.25)     { $result = 'WON'; $percentage = 100; }
        elseif ($diff == -0.25){ $result = 'HALF_WON'; $percentage = 50; }
        elseif ($diff == 0)    { $result = 'PUSH'; $percentage = 0; }
        elseif ($diff == 0.25) { $result = 'HALF_LOST'; $percentage = -50; }
        else                   { $result = 'LOST'; $percentage = -100; }
    }
}

// --- SECTION C: FINANCIAL RESULT ---
echo "<h3>3. Financial Result</h3>";

$payout = 0;
$statusColor = 'black';

if ($result == 'WON') {
    // Formula: Stake * Odds
    // Note: If odds include stake (Decimal), output is Stake * Odds.
    // If odds are Malay/HK, logic differs. Assuming Decimal here:
    $payout = $stake * $odds; 
    $statusColor = 'green';
} 
elseif ($result == 'HALF_WON') {
    // Formula: (Stake * Odds - Stake) / 2 + Stake
    // Or simpler: ((Odds - 1) / 2 + 1) * Stake
    $profit = ($stake * $odds) - $stake;
    $payout = ($profit / 2) + $stake;
    $statusColor = 'darkgreen';
}
elseif ($result == 'HALF_LOST') {
    // Formula: Return half stake
    $payout = $stake / 2;
    $statusColor = 'orange';
}
elseif ($result == 'PUSH') {
    $payout = $stake; // Refund
    $statusColor = 'blue';
}
elseif ($result == 'LOST') {
    $payout = 0;
    $statusColor = 'red';
}

echo "<ul>";
echo "<li><strong>Calculated Status:</strong> <span style='color:$statusColor; font-weight:bold;'>$result</span></li>";
echo "<li><strong>Calculated Payout:</strong> " . number_format($payout, 2) . "</li>";
echo "</ul>";

echo "<h3>4. Comparison</h3>";
echo "<table border='1' cellpadding='5' style='border-collapse:collapse; width:100%'>";
echo "<tr><th>Field</th><th>In Database (Current)</th><th>Calculated (Correct)</th></tr>";
echo "<tr><td>Status</td><td>{$ticket->status}</td><td style='color:$statusColor'><b>$result</b></td></tr>";
echo "<tr><td>Win Amount</td><td>" . number_format($ticket->potential_return ?? 0) . "</td><td>" . number_format($payout) . "</td></tr>";
echo "</table>";

echo "<br><hr>";
echo "<strong>Action:</strong> If 'Calculated' is correct and 'Database' is wrong, you need to manually update the DB or fix the Settlement Controller logic for <em>$selection</em> bets.";

echo "</div>";
?>