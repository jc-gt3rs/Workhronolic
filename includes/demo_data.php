<?php
/**
 * DEMO DATA — placeholder rows so the frontend renders meaningfully.
 * BACKEND TODO: delete this file and replace every usage with prepared
 * mysqli/PDO queries against the real tables.
 */

$DEMO_ENTRIES = [
    ['id' => 5, 'date' => '2026-07-08', 'start' => '09:00', 'end' => null,    'hours' => null, 'status' => 'active',
     'note' => 'Working on the client onboarding flow — wiring the form validation.'],
    ['id' => 4, 'date' => '2026-07-07', 'start' => '13:00', 'end' => '17:30', 'hours' => 4.5,  'status' => 'pending',
     'note' => 'Finished the pricing page revisions and pushed the responsive fixes for mobile breakpoints.'],
    ['id' => 3, 'date' => '2026-07-06', 'start' => '09:15', 'end' => '12:45', 'hours' => 3.5,  'status' => 'approved',
     'note' => 'Wrote unit tests for the invoice module and fixed two rounding bugs found along the way.'],
    ['id' => 2, 'date' => '2026-07-04', 'start' => '10:00', 'end' => '14:00', 'hours' => 4.0,  'status' => 'approved',
     'note' => 'Sprint planning with the team, then drafted the API contract for the reports endpoint.'],
    ['id' => 1, 'date' => '2026-07-03', 'start' => '08:30', 'end' => '12:30', 'hours' => 4.0,  'status' => 'rejected',
     'note' => 'Reviewed pull requests and updated the deployment documentation.'],
];

$DEMO_USERS = [
    ['id' => 1, 'name' => 'Dathan Ancheta',  'email' => 'dathan@startup.io', 'role' => 'admin',  'expected_hours' => 0,  'active' => true],
    ['id' => 2, 'name' => 'John Cris Antor', 'email' => 'jc@startup.io',     'role' => 'worker', 'expected_hours' => 60, 'active' => true],
    ['id' => 3, 'name' => 'Mia Santos',      'email' => 'mia@startup.io',    'role' => 'worker', 'expected_hours' => 80, 'active' => true],
    ['id' => 4, 'name' => 'Paolo Reyes',     'email' => 'paolo@startup.io',  'role' => 'worker', 'expected_hours' => 40, 'active' => false],
];

$DEMO_PENDING = [
    ['id' => 4, 'worker' => 'John Cris Antor', 'date' => '2026-07-07', 'start' => '13:00', 'end' => '17:30', 'hours' => 4.5,
     'note' => 'Finished the pricing page revisions and pushed the responsive fixes for mobile breakpoints.'],
    ['id' => 9, 'worker' => 'Mia Santos',      'date' => '2026-07-07', 'start' => '09:00', 'end' => '15:00', 'hours' => 6.0,
     'note' => 'Migrated the analytics dashboard to the new charting library and verified the weekly numbers.'],
    ['id' => 8, 'worker' => 'Mia Santos',      'date' => '2026-07-06', 'start' => '10:00', 'end' => '13:00', 'hours' => 3.0,
     'note' => 'Customer support rotation — closed 11 tickets and documented two recurring issues.'],
];

$DEMO_REPORT = [
    ['worker' => 'John Cris Antor', 'expected' => 60, 'verified' => 52.5, 'pending' => 4.5, 'entries' => 14],
    ['worker' => 'Mia Santos',      'expected' => 80, 'verified' => 78.0, 'pending' => 9.0, 'entries' => 21],
    ['worker' => 'Paolo Reyes',     'expected' => 40, 'verified' => 12.0, 'pending' => 0.0, 'entries' => 4],
];
