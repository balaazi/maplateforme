<?php
/**
 * Test simple pour vérifier la détection de conflit d'horaires
 * Ce fichier peut être exécuté directement pour tester la logique
 */

// Simulation de la logique de détection de conflit
function checkScheduleConflict($newEventStart, $newEventDuration, $existingEventStart, $existingEventDuration) {
    $newEventEnd = clone $newEventStart;
    $newEventEnd->modify('+' . $newEventDuration . ' minutes');
    
    $existingEventEnd = clone $existingEventStart;
    $existingEventEnd->modify('+' . $existingEventDuration . ' minutes');
    
    // Vérifier s'il y a chevauchement
    if ($newEventStart < $existingEventEnd && $newEventEnd > $existingEventStart) {
        return true; // Conflit détecté
    }
    
    return false; // Aucun conflit
}

// Test 1: Événements qui se chevauchent
echo "=== Test 1: Événements qui se chevauchent ===\n";
$event1Start = new DateTime('2024-01-15 14:00:00');
$event1Duration = 60; // 1 heure
$event2Start = new DateTime('2024-01-15 14:30:00');
$event2Duration = 90; // 1h30

$hasConflict = checkScheduleConflict($event1Start, $event1Duration, $event2Start, $event2Duration);
echo "Conflit détecté: " . ($hasConflict ? 'OUI' : 'NON') . "\n";
echo "Événement 1: " . $event1Start->format('H:i') . " - " . $event1Start->modify('+' . $event1Duration . ' minutes')->format('H:i') . "\n";
echo "Événement 2: " . $event2Start->format('H:i') . " - " . $event2Start->modify('+' . $event2Duration . ' minutes')->format('H:i') . "\n\n";

// Test 2: Événements consécutifs (pas de conflit)
echo "=== Test 2: Événements consécutifs (pas de conflit) ===\n";
$event3Start = new DateTime('2024-01-15 14:00:00');
$event3Duration = 60; // 1 heure
$event4Start = new DateTime('2024-01-15 15:00:00');
$event4Duration = 90; // 1h30

$hasConflict = checkScheduleConflict($event3Start, $event3Duration, $event4Start, $event4Duration);
echo "Conflit détecté: " . ($hasConflict ? 'OUI' : 'NON') . "\n";
echo "Événement 3: " . $event3Start->format('H:i') . " - " . $event3Start->modify('+' . $event3Duration . ' minutes')->format('H:i') . "\n";
echo "Événement 4: " . $event4Start->format('H:i') . " - " . $event4Start->modify('+' . $event4Duration . ' minutes')->format('H:i') . "\n\n";

// Test 3: Événements sur des jours différents (pas de conflit)
echo "=== Test 3: Événements sur des jours différents (pas de conflit) ===\n";
$event5Start = new DateTime('2024-01-15 14:00:00');
$event5Duration = 60; // 1 heure
$event6Start = new DateTime('2024-01-16 14:00:00');
$event6Duration = 90; // 1h30

$hasConflict = checkScheduleConflict($event5Start, $event5Duration, $event6Start, $event6Duration);
echo "Conflit détecté: " . ($hasConflict ? 'OUI' : 'NON') . "\n";
echo "Événement 5: " . $event5Start->format('d/m/Y H:i') . " - " . $event5Start->modify('+' . $event5Duration . ' minutes')->format('H:i') . "\n";
echo "Événement 6: " . $event6Start->format('d/m/Y H:i') . " - " . $event6Start->modify('+' . $event6Duration . ' minutes')->format('H:i') . "\n\n";

echo "Tests terminés !\n";
?>
