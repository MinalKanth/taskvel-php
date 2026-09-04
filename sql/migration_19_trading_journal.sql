-- ============================================================
-- MIGRATION 19 — Trading Journal & Profit/Loss Dashboard
-- MULTI-USER DUMMY DATA VERSION
--
-- Seeds realistic trading/journal demo data for multiple users.
-- User IDs are always resolved from email addresses.
--
-- Tables:
--   1. trading_goals
--   2. trading_entries
--   3. trading_journal
--
-- Safe to re-run:
--   - Goals are updated instead of duplicated
--   - Journal entries are updated instead of duplicated
--   - Trading entries are inserted only when the same seed entry
--     does not already exist for that user/date/note combination.
-- ============================================================


-- ============================================================
-- 1) MONTHLY GOALS
-- ============================================================

CREATE TABLE IF NOT EXISTS trading_goals (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id         INT UNSIGNED    NOT NULL,
    month           CHAR(7)         NOT NULL,
    target_amount   DECIMAL(12,2)   NOT NULL DEFAULT 0,
    created_at      DATETIME        DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        DEFAULT CURRENT_TIMESTAMP
                                    ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    UNIQUE KEY uniq_user_month (user_id, month),

    CONSTRAINT fk_trading_goals_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================================
-- 2) DAILY P&L ENTRIES
-- ============================================================

CREATE TABLE IF NOT EXISTS trading_entries (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id         INT UNSIGNED    NOT NULL,
    entry_date      DATE            NOT NULL,
    pnl_amount      DECIMAL(12,2)   NOT NULL DEFAULT 0,
    status          ENUM(
                        'profit',
                        'loss',
                        'breakeven'
                    ) NOT NULL DEFAULT 'profit',
    notes           VARCHAR(500)    NULL,
    created_at      DATETIME        DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        DEFAULT CURRENT_TIMESTAMP
                                    ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    KEY idx_user_date (user_id, entry_date)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================================
-- ADD FOREIGN KEY SAFELY
-- ============================================================

SET @fk_exists := (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND CONSTRAINT_NAME = 'fk_trading_entries_user'
);

SET @sql := IF(
    @fk_exists = 0,
    'ALTER TABLE trading_entries
     ADD CONSTRAINT fk_trading_entries_user
     FOREIGN KEY (user_id)
     REFERENCES users(id)
     ON DELETE CASCADE',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- ============================================================
-- 3) DAILY JOURNAL
-- ============================================================

CREATE TABLE IF NOT EXISTS trading_journal (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id         INT UNSIGNED    NOT NULL,
    entry_date      DATE            NOT NULL,
    content         TEXT            NOT NULL,
    created_at      DATETIME        DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        DEFAULT CURRENT_TIMESTAMP
                                    ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    UNIQUE KEY uniq_user_date (user_id, entry_date),

    CONSTRAINT fk_trading_journal_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================================
-- DUMMY USERS
-- ============================================================

DROP TEMPORARY TABLE IF EXISTS tmp_trading_demo_users;

CREATE TEMPORARY TABLE tmp_trading_demo_users (
    email VARCHAR(255) NOT NULL PRIMARY KEY
);


INSERT INTO tmp_trading_demo_users (email)
VALUES
    ('anuragshriwastava.in@gmail.com'),
    ('biomeentrprize@gmail.com'),
    ('chitoranjan.sahu@viprak.com'),
    ('hrudanandasamal01@gmail.com'),
    ('hrudanandasarma02@gmail.com'),
    ('kuljeetsingh2419@yopmai.com'),
    ('minal@gmail.com'),
    ('minal@user.com'),
    ('minaladmin@user.com'),
    ('minalkanth.9@gmail.com');


-- ============================================================
-- CHECK WHICH USERS WERE FOUND
-- ============================================================

SELECT
    t.email,
    u.id AS user_id,
    CASE
        WHEN u.id IS NULL THEN 'NOT FOUND'
        ELSE 'READY'
    END AS seed_status
FROM tmp_trading_demo_users t
LEFT JOIN users u
    ON u.email = t.email
ORDER BY t.email;


-- ============================================================
-- OPTIONAL SAFETY MESSAGE
-- ============================================================

SELECT CONCAT(
    'Found ',
    COUNT(u.id),
    ' of ',
    (SELECT COUNT(*) FROM tmp_trading_demo_users),
    ' demo users.'
) AS seed_summary
FROM tmp_trading_demo_users t
LEFT JOIN users u
    ON u.email = t.email;


-- ============================================================
-- MONTHLY GOALS
-- ============================================================
--
-- Every demo user gets:
--   Current month  = ₹2,000 target
--   Previous month = ₹1,500 target
--
-- ============================================================

INSERT INTO trading_goals (
    user_id,
    month,
    target_amount
)

SELECT
    u.id,
    DATE_FORMAT(CURDATE(), '%Y-%m'),
    2000.00

FROM users u

INNER JOIN tmp_trading_demo_users t
    ON t.email = u.email

UNION ALL

SELECT
    u.id,
    DATE_FORMAT(
        DATE_SUB(CURDATE(), INTERVAL 1 MONTH),
        '%Y-%m'
    ),
    1500.00

FROM users u

INNER JOIN tmp_trading_demo_users t
    ON t.email = u.email

ON DUPLICATE KEY UPDATE
    target_amount = VALUES(target_amount);


-- ============================================================
-- PREVIOUS MONTH — TRADING ENTRIES
-- ============================================================
--
-- Full realistic mixed trading month.
-- The same pattern is generated for every demo user.
--
-- ============================================================

INSERT INTO trading_entries (
    user_id,
    entry_date,
    pnl_amount,
    status,
    notes
)

SELECT
    u.id,
    x.entry_date,
    x.pnl_amount,
    x.status,
    x.notes

FROM users u

INNER JOIN tmp_trading_demo_users demo
    ON demo.email = u.email

CROSS JOIN (

    SELECT
        DATE_SUB(
            DATE_FORMAT(CURDATE(), '%Y-%m-01'),
            INTERVAL 27 DAY
        ) AS entry_date,
        120.50 AS pnl_amount,
        'profit' AS status,
        'Clean breakout trade on EURUSD' AS notes

    UNION ALL

    SELECT
        DATE_SUB(
            DATE_FORMAT(CURDATE(), '%Y-%m-01'),
            INTERVAL 26 DAY
        ),
        -45.00,
        'loss',
        'Entered too early, stopped out'

    UNION ALL

    SELECT
        DATE_SUB(
            DATE_FORMAT(CURDATE(), '%Y-%m-01'),
            INTERVAL 25 DAY
        ),
        0.00,
        'breakeven',
        'Choppy session, closed flat'

    UNION ALL

    SELECT
        DATE_SUB(
            DATE_FORMAT(CURDATE(), '%Y-%m-01'),
            INTERVAL 24 DAY
        ),
        210.00,
        'profit',
        'Good trend day on gold'

    UNION ALL

    SELECT
        DATE_SUB(
            DATE_FORMAT(CURDATE(), '%Y-%m-01'),
            INTERVAL 23 DAY
        ),
        75.25,
        'profit',
        'Small scalp profits'

    UNION ALL

    SELECT
        DATE_SUB(
            DATE_FORMAT(CURDATE(), '%Y-%m-01'),
            INTERVAL 20 DAY
        ),
        -120.00,
        'loss',
        'Revenge traded after first loss, avoid this'

    UNION ALL

    SELECT
        DATE_SUB(
            DATE_FORMAT(CURDATE(), '%Y-%m-01'),
            INTERVAL 19 DAY
        ),
        300.00,
        'profit',
        'Best trade of the month, followed the plan'

    UNION ALL

    SELECT
        DATE_SUB(
            DATE_FORMAT(CURDATE(), '%Y-%m-01'),
            INTERVAL 18 DAY
        ),
        60.00,
        'profit',
        NULL

    UNION ALL

    SELECT
        DATE_SUB(
            DATE_FORMAT(CURDATE(), '%Y-%m-01'),
            INTERVAL 17 DAY
        ),
        -30.00,
        'loss',
        'News spike caught me off guard'

    UNION ALL

    SELECT
        DATE_SUB(
            DATE_FORMAT(CURDATE(), '%Y-%m-01'),
            INTERVAL 14 DAY
        ),
        95.00,
        'profit',
        NULL

    UNION ALL

    SELECT
        DATE_SUB(
            DATE_FORMAT(CURDATE(), '%Y-%m-01'),
            INTERVAL 13 DAY
        ),
        -60.00,
        'loss',
        'Oversized position, need smaller lots'

    UNION ALL

    SELECT
        DATE_SUB(
            DATE_FORMAT(CURDATE(), '%Y-%m-01'),
            INTERVAL 12 DAY
        ),
        180.00,
        'profit',
        'Solid follow-through on plan'

    UNION ALL

    SELECT
        DATE_SUB(
            DATE_FORMAT(CURDATE(), '%Y-%m-01'),
            INTERVAL 11 DAY
        ),
        40.00,
        'profit',
        NULL

    UNION ALL

    SELECT
        DATE_SUB(
            DATE_FORMAT(CURDATE(), '%Y-%m-01'),
            INTERVAL 10 DAY
        ),
        -25.00,
        'loss',
        'Small loss, stayed disciplined'

    UNION ALL

    SELECT
        DATE_SUB(
            DATE_FORMAT(CURDATE(), '%Y-%m-01'),
            INTERVAL 7 DAY
        ),
        250.00,
        'profit',
        'Trend day, scaled in nicely'

    UNION ALL

    SELECT
        DATE_SUB(
            DATE_FORMAT(CURDATE(), '%Y-%m-01'),
            INTERVAL 6 DAY
        ),
        -80.00,
        'loss',
        'Broke my own rules on stop loss'

    UNION ALL

    SELECT
        DATE_SUB(
            DATE_FORMAT(CURDATE(), '%Y-%m-01'),
            INTERVAL 3 DAY
        ),
        150.00,
        'profit',
        'Clean continuation setup'

    UNION ALL

    SELECT
        DATE_SUB(
            DATE_FORMAT(CURDATE(), '%Y-%m-01'),
            INTERVAL 1 DAY
        ),
        55.00,
        'profit',
        'Steady close to the month'

) x

WHERE NOT EXISTS (

    SELECT 1

    FROM trading_entries existing

    WHERE existing.user_id = u.id
      AND existing.entry_date = x.entry_date
      AND existing.pnl_amount = x.pnl_amount
      AND (
          existing.notes = x.notes
          OR (
              existing.notes IS NULL
              AND x.notes IS NULL
          )
      )

);


-- ============================================================
-- CURRENT MONTH — REALISTIC PARTIAL DATA
-- ============================================================
--
-- Only dates up to today are inserted.
-- This prevents future trading records from appearing.
--
-- ============================================================

INSERT INTO trading_entries (
    user_id,
    entry_date,
    pnl_amount,
    status,
    notes
)

SELECT
    u.id,
    x.entry_date,
    x.pnl_amount,
    x.status,
    x.notes

FROM users u

INNER JOIN tmp_trading_demo_users demo
    ON demo.email = u.email

CROSS JOIN (

    SELECT
        DATE_FORMAT(CURDATE(), '%Y-%m-01') AS entry_date,
        140.00 AS pnl_amount,
        'profit' AS status,
        'Strong start to the month' AS notes

    UNION ALL

    SELECT
        DATE_ADD(
            DATE_FORMAT(CURDATE(), '%Y-%m-01'),
            INTERVAL 1 DAY
        ),
        -40.00,
        'loss',
        'Gave back some gains'

    UNION ALL

    SELECT
        DATE_ADD(
            DATE_FORMAT(CURDATE(), '%Y-%m-01'),
            INTERVAL 2 DAY
        ),
        0.00,
        'breakeven',
        'Sat out most of the session'

    UNION ALL

    SELECT
        DATE_ADD(
            DATE_FORMAT(CURDATE(), '%Y-%m-01'),
            INTERVAL 3 DAY
        ),
        220.00,
        'profit',
        'Great trend continuation trade'

    UNION ALL

    SELECT
        DATE_ADD(
            DATE_FORMAT(CURDATE(), '%Y-%m-01'),
            INTERVAL 4 DAY
        ),
        90.00,
        'profit',
        'Followed the setup exactly'

    UNION ALL

    SELECT
        DATE_ADD(
            DATE_FORMAT(CURDATE(), '%Y-%m-01'),
            INTERVAL 5 DAY
        ),
        -55.00,
        'loss',
        'Ignored my checklist, paid for it'

    UNION ALL

    SELECT
        DATE_ADD(
            DATE_FORMAT(CURDATE(), '%Y-%m-01'),
            INTERVAL 6 DAY
        ),
        310.00,
        'profit',
        'Best day this month'

    UNION ALL

    SELECT
        DATE_ADD(
            DATE_FORMAT(CURDATE(), '%Y-%m-01'),
            INTERVAL 7 DAY
        ),
        45.00,
        'profit',
        'Small but disciplined profit'

    UNION ALL

    SELECT
        DATE_ADD(
            DATE_FORMAT(CURDATE(), '%Y-%m-01'),
            INTERVAL 8 DAY
        ),
        -70.00,
        'loss',
        'Two losses back to back, stopped trading for the day'

    UNION ALL

    SELECT
        DATE_ADD(
            DATE_FORMAT(CURDATE(), '%Y-%m-01'),
            INTERVAL 9 DAY
        ),
        125.00,
        'profit',
        'Recovered with a clean A+ setup'

    UNION ALL

    SELECT
        DATE_ADD(
            DATE_FORMAT(CURDATE(), '%Y-%m-01'),
            INTERVAL 10 DAY
        ),
        80.00,
        'profit',
        'Patient entry after confirmation'

) x

WHERE x.entry_date <= CURDATE()

AND NOT EXISTS (

    SELECT 1

    FROM trading_entries existing

    WHERE existing.user_id = u.id
      AND existing.entry_date = x.entry_date
      AND existing.pnl_amount = x.pnl_amount
      AND (
          existing.notes = x.notes
          OR (
              existing.notes IS NULL
              AND x.notes IS NULL
          )
      )

);


-- ============================================================
-- JOURNAL ENTRIES
-- ============================================================
--
-- Each user receives realistic daily journal content.
--
-- ============================================================

INSERT INTO trading_journal (
    user_id,
    entry_date,
    content
)

SELECT
    u.id,
    x.entry_date,
    x.content

FROM users u

INNER JOIN tmp_trading_demo_users demo
    ON demo.email = u.email

CROSS JOIN (

    -- --------------------------------------------------------
    -- DAY 1 — STRONG SESSION
    -- --------------------------------------------------------

    SELECT
        DATE_FORMAT(
            CURDATE(),
            '%Y-%m-01'
        ) AS entry_date,

        'How the day went: Strong, focused session, followed my plan closely.
Experience: Waited patiently for the A+ setup instead of forcing trades.
Emotions/mindset: Calm and confident, no FOMO today.
Mistakes: None major — one entry was a few pips late.
Lessons learned: Patience at the open pays off more than rushing in.
Observations: Volume was high in the first hour, then thinned out by midday.'
        AS content

    UNION ALL

    -- --------------------------------------------------------
    -- DAY 2 — DIFFICULT SESSION
    -- --------------------------------------------------------

    SELECT
        DATE_ADD(
            DATE_FORMAT(CURDATE(), '%Y-%m-01'),
            INTERVAL 1 DAY
        ),

        'How the day went: Started well but gave back part of the profit.
Experience: Took a second trade without enough confirmation.
Emotions/mindset: Became slightly impatient after the first winning trade.
Mistakes: Entered too quickly and moved the stop unnecessarily.
Lessons learned: Protect profits by sticking to the original plan.
Observations: Market became choppy after the initial move.'

    UNION ALL

    -- --------------------------------------------------------
    -- DAY 3 — BREAKEVEN
    -- --------------------------------------------------------

    SELECT
        DATE_ADD(
            DATE_FORMAT(CURDATE(), '%Y-%m-01'),
            INTERVAL 2 DAY
        ),

        'How the day went: Quiet session with very limited opportunities.
Experience: Watched the market without forcing a trade.
Emotions/mindset: Calm and patient.
Mistakes: None — avoided low-quality setups.
Lessons learned: Staying out is also a trading decision.
Observations: Price remained range-bound for most of the session.'

    UNION ALL

    -- --------------------------------------------------------
    -- DAY 4 — WINNING SESSION
    -- --------------------------------------------------------

    SELECT
        DATE_ADD(
            DATE_FORMAT(CURDATE(), '%Y-%m-01'),
            INTERVAL 3 DAY
        ),

        'How the day went: Excellent trend continuation session.
Experience: Waited for confirmation before entering.
Emotions/mindset: Relaxed and confident.
Mistakes: Took profit slightly earlier than planned.
Lessons learned: Trust the setup when all conditions are aligned.
Observations: Higher timeframe structure provided a very clear direction.'

    UNION ALL

    -- --------------------------------------------------------
    -- DAY 5 — PROFIT
    -- --------------------------------------------------------

    SELECT
        DATE_ADD(
            DATE_FORMAT(CURDATE(), '%Y-%m-01'),
            INTERVAL 4 DAY
        ),

        'How the day went: Good controlled trading session.
Experience: Took one high-quality setup and managed risk carefully.
Emotions/mindset: Focused throughout the trade.
Mistakes: None significant.
Lessons learned: One quality trade is better than multiple average trades.
Observations: Clean price action made the entry easier to manage.'

    UNION ALL

    -- --------------------------------------------------------
    -- DAY 6 — LOSS
    -- --------------------------------------------------------

    SELECT
        DATE_ADD(
            DATE_FORMAT(CURDATE(), '%Y-%m-01'),
            INTERVAL 5 DAY
        ),

        'How the day went: Difficult day with one avoidable loss.
Experience: Entered before the setup was fully confirmed.
Emotions/mindset: Slightly rushed after missing an earlier move.
Mistakes: Ignored the checklist and entered too early.
Lessons learned: Never chase a move after it has already started.
Observations: Volatility increased quickly and invalidated the setup.'

    UNION ALL

    -- --------------------------------------------------------
    -- DAY 7 — BEST DAY
    -- --------------------------------------------------------

    SELECT
        DATE_ADD(
            DATE_FORMAT(CURDATE(), '%Y-%m-01'),
            INTERVAL 6 DAY
        ),

        'How the day went: Best trading day of the month so far.
Experience: Scaled into a strong confirmed trend.
Emotions/mindset: Calm and disciplined from start to finish.
Mistakes: None — followed the plan exactly.
Lessons learned: Let winners develop instead of taking profit too quickly.
Observations: Strong momentum continued after the initial breakout.'

    UNION ALL

    -- --------------------------------------------------------
    -- DAY 8 — SMALL PROFIT
    -- --------------------------------------------------------

    SELECT
        DATE_ADD(
            DATE_FORMAT(CURDATE(), '%Y-%m-01'),
            INTERVAL 7 DAY
        ),

        'How the day went: Small but consistent profitable session.
Experience: Took only one setup during the active session.
Emotions/mindset: Patient and selective.
Mistakes: Could have waited slightly longer for confirmation.
Lessons learned: Consistency matters more than chasing large wins.
Observations: Market structure remained clean during the main session.'

    UNION ALL

    -- --------------------------------------------------------
    -- DAY 9 — LOSS
    -- --------------------------------------------------------

    SELECT
        DATE_ADD(
            DATE_FORMAT(CURDATE(), '%Y-%m-01'),
            INTERVAL 8 DAY
        ),

        'How the day went: Two losses back to back.
Experience: First trade was reasonable, second trade was unnecessary.
Emotions/mindset: Started feeling frustrated after the second loss.
Mistakes: Continued trading after losing focus.
Lessons learned: Stop trading when the mental state changes.
Observations: Market conditions were not suitable for the strategy.'

    UNION ALL

    -- --------------------------------------------------------
    -- DAY 10 — RECOVERY
    -- --------------------------------------------------------

    SELECT
        DATE_ADD(
            DATE_FORMAT(CURDATE(), '%Y-%m-01'),
            INTERVAL 9 DAY
        ),

        'How the day went: Recovered with a clean A+ setup.
Experience: Waited for multiple confirmations before entering.
Emotions/mindset: More disciplined after yesterday’s losses.
Mistakes: None major.
Lessons learned: A good recovery comes from following the process, not revenge trading.
Observations: Confirmation from the higher timeframe improved trade quality.'

    UNION ALL

    -- --------------------------------------------------------
    -- DAY 11 — PATIENT SESSION
    -- --------------------------------------------------------

    SELECT
        DATE_ADD(
            DATE_FORMAT(CURDATE(), '%Y-%m-01'),
            INTERVAL 10 DAY
        ),

        'How the day went: Patient and controlled session.
Experience: Waited for the market to come to the planned entry zone.
Emotions/mindset: Confident without being over-aggressive.
Mistakes: None.
Lessons learned: Preparation makes execution much easier.
Observations: The cleanest opportunities appeared later in the session.'

) x

WHERE x.entry_date <= CURDATE()

ON DUPLICATE KEY UPDATE
    content = VALUES(content);


-- ============================================================
-- FINAL SUMMARY
-- ============================================================

SELECT
    u.id AS user_id,
    u.email,

    (
        SELECT COUNT(*)
        FROM trading_entries te
        WHERE te.user_id = u.id
    ) AS trading_entries,

    (
        SELECT COUNT(*)
        FROM trading_journal tj
        WHERE tj.user_id = u.id
    ) AS journal_entries,

    (
        SELECT COUNT(*)
        FROM trading_goals tg
        WHERE tg.user_id = u.id
    ) AS goals

FROM users u

INNER JOIN tmp_trading_demo_users demo
    ON demo.email = u.email

ORDER BY u.id;


-- ============================================================
-- CLEANUP
-- ============================================================

DROP TEMPORARY TABLE IF EXISTS tmp_trading_demo_users;


-- ============================================================
-- END MIGRATION 19
-- ============================================================