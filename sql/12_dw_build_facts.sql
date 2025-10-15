-- CS437 MLB Global Era - Build Data Warehouse Facts
-- 
-- This file populates the fact tables and canonical player_season table
-- in the dw schema by aggregating and joining source data.
--
-- Prerequisites:
--   - 10_dw_schema.sql and 11_dw_build_dimensions.sql must be run first
--   - Source data must be loaded (Lahman, WAR, etc.)
--
-- Usage:
--   psql -d mlb -f 12_dw_build_facts.sql

-- ==========================================================================
-- Populate Player-Teams Bridge
-- ==========================================================================

\echo 'Populating player_teams bridge...'

-- Aggregate appearances to get player-team associations
INSERT INTO dw.player_teams (year, player_id, team_id, g, primary_pos)
SELECT 
    year_id AS year,
    player_id,
    team_id,
    g_all AS g,
    -- Determine primary position (most games played)
    CASE 
        WHEN g_p > 0 THEN 'P'
        WHEN g_c > COALESCE(g_1b, 0) AND g_c > COALESCE(g_2b, 0) AND g_c > COALESCE(g_3b, 0) 
             AND g_c > COALESCE(g_ss, 0) AND g_c > COALESCE(g_of, 0) THEN 'C'
        WHEN g_1b > COALESCE(g_2b, 0) AND g_1b > COALESCE(g_3b, 0) 
             AND g_1b > COALESCE(g_ss, 0) AND g_1b > COALESCE(g_of, 0) THEN '1B'
        WHEN g_2b > COALESCE(g_3b, 0) AND g_2b > COALESCE(g_ss, 0) 
             AND g_2b > COALESCE(g_of, 0) THEN '2B'
        WHEN g_3b > COALESCE(g_ss, 0) AND g_3b > COALESCE(g_of, 0) THEN '3B'
        WHEN g_ss > COALESCE(g_of, 0) THEN 'SS'
        WHEN g_of > 0 THEN 'OF'
        WHEN g_dh > 0 THEN 'DH'
        ELSE NULL
    END AS primary_pos
FROM core.appearances
WHERE g_all > 0
ON CONFLICT (year, player_id, team_id) DO UPDATE SET
    g = EXCLUDED.g,
    primary_pos = EXCLUDED.primary_pos;

\echo 'Player-teams bridge populated'

-- ==========================================================================
-- Populate Batting Season Facts
-- ==========================================================================

\echo 'Aggregating batting statistics by player-season...'

INSERT INTO dw.batting_season (
    year,
    player_id,
    ab,
    h,
    hr,
    rbi,
    bb,
    so,
    sb,
    cs,
    obp,
    slg,
    ops
)
SELECT 
    year_id AS year,
    player_id,
    SUM(ab) AS ab,
    SUM(h) AS h,
    SUM(hr) AS hr,
    SUM(rbi) AS rbi,
    SUM(bb) AS bb,
    SUM(so) AS so,
    SUM(sb) AS sb,
    SUM(cs) AS cs,
    -- Calculate OBP: (H + BB + HBP) / (AB + BB + HBP + SF)
    CASE 
        WHEN SUM(ab) + SUM(bb) + COALESCE(SUM(hbp), 0) + COALESCE(SUM(sf), 0) > 0 
        THEN ROUND(
            (SUM(h) + SUM(bb) + COALESCE(SUM(hbp), 0))::NUMERIC / 
            NULLIF(SUM(ab) + SUM(bb) + COALESCE(SUM(hbp), 0) + COALESCE(SUM(sf), 0), 0),
            3
        )
        ELSE NULL
    END AS obp,
    -- Calculate SLG: Total Bases / AB
    CASE 
        WHEN SUM(ab) > 0 
        THEN ROUND(
            (SUM(h) + SUM(double) + 2 * SUM(triple) + 3 * SUM(hr))::NUMERIC / 
            NULLIF(SUM(ab), 0),
            3
        )
        ELSE NULL
    END AS slg,
    -- Calculate OPS: OBP + SLG
    CASE 
        WHEN SUM(ab) + SUM(bb) + COALESCE(SUM(hbp), 0) + COALESCE(SUM(sf), 0) > 0 
             AND SUM(ab) > 0 
        THEN ROUND(
            (SUM(h) + SUM(bb) + COALESCE(SUM(hbp), 0))::NUMERIC / 
            NULLIF(SUM(ab) + SUM(bb) + COALESCE(SUM(hbp), 0) + COALESCE(SUM(sf), 0), 0) +
            (SUM(h) + SUM(double) + 2 * SUM(triple) + 3 * SUM(hr))::NUMERIC / 
            NULLIF(SUM(ab), 0),
            3
        )
        ELSE NULL
    END AS ops
FROM core.batting
GROUP BY year_id, player_id
HAVING SUM(ab) > 0  -- Only include players with actual at-bats
ON CONFLICT (year, player_id) DO UPDATE SET
    ab = EXCLUDED.ab,
    h = EXCLUDED.h,
    hr = EXCLUDED.hr,
    rbi = EXCLUDED.rbi,
    bb = EXCLUDED.bb,
    so = EXCLUDED.so,
    sb = EXCLUDED.sb,
    cs = EXCLUDED.cs,
    obp = EXCLUDED.obp,
    slg = EXCLUDED.slg,
    ops = EXCLUDED.ops;

\echo 'Batting season facts populated'

-- ==========================================================================
-- Populate Pitching Season Facts
-- ==========================================================================

\echo 'Aggregating pitching statistics by player-season...'

INSERT INTO dw.pitching_season (
    year,
    player_id,
    ip_outs,
    er,
    so,
    bb,
    hr_allowed,
    era,
    sv,
    w,
    l
)
SELECT 
    year_id AS year,
    player_id,
    SUM(ipouts) AS ip_outs,
    SUM(er) AS er,
    SUM(so) AS so,
    SUM(bb) AS bb,
    SUM(hr) AS hr_allowed,
    -- Calculate ERA: (ER * 9) / (IPouts / 3)
    CASE 
        WHEN SUM(ipouts) > 0 
        THEN ROUND((9.0 * SUM(er)) / (SUM(ipouts)::NUMERIC / 3.0), 2)
        ELSE NULL
    END AS era,
    SUM(sv) AS sv,
    SUM(w) AS w,
    SUM(l) AS l
FROM core.pitching
GROUP BY year_id, player_id
HAVING SUM(ipouts) > 0  -- Only include pitchers with actual innings
ON CONFLICT (year, player_id) DO UPDATE SET
    ip_outs = EXCLUDED.ip_outs,
    er = EXCLUDED.er,
    so = EXCLUDED.so,
    bb = EXCLUDED.bb,
    hr_allowed = EXCLUDED.hr_allowed,
    era = EXCLUDED.era,
    sv = EXCLUDED.sv,
    w = EXCLUDED.w,
    l = EXCLUDED.l;

\echo 'Pitching season facts populated'

-- ==========================================================================
-- Populate Fielding Season Facts (Optional)
-- ==========================================================================

\echo 'Aggregating fielding statistics by player-season-position...'

-- Check if lahman.fielding or core.fielding exists
DO $$ 
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = 'lahman' AND table_name = 'fielding') THEN
        INSERT INTO dw.fielding_season (
            year,
            player_id,
            pos,
            g,
            gs,
            inn_outs,
            po,
            a,
            e,
            fld_pct
        )
        SELECT 
            yearid AS year,
            playerid AS player_id,
            pos,
            SUM(g) AS g,
            SUM(gs) AS gs,
            SUM(innouts) AS inn_outs,
            SUM(po) AS po,
            SUM(a) AS a,
            SUM(e) AS e,
            -- Calculate fielding percentage: (PO + A) / (PO + A + E)
            CASE 
                WHEN SUM(po) + SUM(a) + SUM(e) > 0 
                THEN ROUND((SUM(po) + SUM(a))::NUMERIC / NULLIF(SUM(po) + SUM(a) + SUM(e), 0), 3)
                ELSE NULL
            END AS fld_pct
        FROM lahman.fielding
        GROUP BY yearid, playerid, pos
        ON CONFLICT (year, player_id, pos) DO UPDATE SET
            g = EXCLUDED.g,
            gs = EXCLUDED.gs,
            inn_outs = EXCLUDED.inn_outs,
            po = EXCLUDED.po,
            a = EXCLUDED.a,
            e = EXCLUDED.e,
            fld_pct = EXCLUDED.fld_pct;
        
        RAISE NOTICE 'Fielding season facts populated from lahman.fielding';
    ELSIF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = 'core' AND table_name = 'fielding') THEN
        INSERT INTO dw.fielding_season (
            year,
            player_id,
            pos,
            g,
            gs,
            inn_outs,
            po,
            a,
            e,
            fld_pct
        )
        SELECT 
            year_id AS year,
            player_id,
            pos,
            SUM(g) AS g,
            SUM(gs) AS gs,
            SUM(inn_outs) AS inn_outs,
            SUM(po) AS po,
            SUM(a) AS a,
            SUM(e) AS e,
            -- Calculate fielding percentage: (PO + A) / (PO + A + E)
            CASE 
                WHEN SUM(po) + SUM(a) + SUM(e) > 0 
                THEN ROUND((SUM(po) + SUM(a))::NUMERIC / NULLIF(SUM(po) + SUM(a) + SUM(e), 0), 3)
                ELSE NULL
            END AS fld_pct
        FROM core.fielding
        GROUP BY year_id, player_id, pos
        ON CONFLICT (year, player_id, pos) DO UPDATE SET
            g = EXCLUDED.g,
            gs = EXCLUDED.gs,
            inn_outs = EXCLUDED.inn_outs,
            po = EXCLUDED.po,
            a = EXCLUDED.a,
            e = EXCLUDED.e,
            fld_pct = EXCLUDED.fld_pct;
        
        RAISE NOTICE 'Fielding season facts populated from core.fielding';
    ELSE
        RAISE NOTICE 'No fielding table found, skipping fielding facts';
    END IF;
END $$;

-- ==========================================================================
-- Populate Awards Season Facts
-- ==========================================================================

\echo 'Aggregating awards by player-season...'

-- First, aggregate standard awards
INSERT INTO dw.awards_season (year, player_id, award_id, count, allstar_count)
SELECT 
    year_id AS year,
    player_id,
    award_id,
    COUNT(*) AS count,
    0 AS allstar_count
FROM core.awardsplayers
GROUP BY year_id, player_id, award_id
ON CONFLICT (year, player_id, award_id) DO UPDATE SET
    count = EXCLUDED.count;

-- Add All-Star counts if available
DO $$ 
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = 'lahman' AND table_name = 'allstarfull') THEN
        -- Update with All-Star counts from lahman
        INSERT INTO dw.awards_season (year, player_id, award_id, count, allstar_count)
        SELECT 
            yearid AS year,
            playerid AS player_id,
            'All-Star' AS award_id,
            COUNT(*) AS count,
            COUNT(*) AS allstar_count
        FROM lahman.allstarfull
        GROUP BY yearid, playerid
        ON CONFLICT (year, player_id, award_id) DO UPDATE SET
            count = EXCLUDED.count,
            allstar_count = EXCLUDED.allstar_count;
        
        RAISE NOTICE 'All-Star awards added from lahman.allstarfull';
    ELSIF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = 'core' AND table_name = 'allstarfull') THEN
        -- Update with All-Star counts from core
        INSERT INTO dw.awards_season (year, player_id, award_id, count, allstar_count)
        SELECT 
            year_id AS year,
            player_id,
            'All-Star' AS award_id,
            COUNT(*) AS count,
            COUNT(*) AS allstar_count
        FROM core.allstarfull
        GROUP BY year_id, player_id
        ON CONFLICT (year, player_id, award_id) DO UPDATE SET
            count = EXCLUDED.count,
            allstar_count = EXCLUDED.allstar_count;
        
        RAISE NOTICE 'All-Star awards added from core.allstarfull';
    END IF;
END $$;

\echo 'Awards season facts populated'

-- ==========================================================================
-- Populate WAR Season Facts
-- ==========================================================================

\echo 'Aggregating WAR by player-season...'

-- Build from typed bref tables if they exist
INSERT INTO dw.war_season (year, player_id, war_bat, war_pitch, war_total)
SELECT 
    COALESCE(b.yearid, p.yearid) AS year,
    COALESCE(b.playerid, p.playerid) AS player_id,
    SUM(b.war) AS war_bat,
    SUM(p.war) AS war_pitch,
    COALESCE(SUM(b.war), 0) + COALESCE(SUM(p.war), 0) AS war_total
FROM (
    SELECT yearid, playerid, SUM(war) AS war
    FROM bref.war_bat
    WHERE yearid IS NOT NULL AND playerid IS NOT NULL
    GROUP BY yearid, playerid
) b
FULL OUTER JOIN (
    SELECT yearid, playerid, SUM(war) AS war
    FROM bref.war_pitch
    WHERE yearid IS NOT NULL AND playerid IS NOT NULL
    GROUP BY yearid, playerid
) p ON b.yearid = p.yearid AND b.playerid = p.playerid
ON CONFLICT (year, player_id) DO UPDATE SET
    war_bat = EXCLUDED.war_bat,
    war_pitch = EXCLUDED.war_pitch,
    war_total = EXCLUDED.war_total;

\echo 'WAR season facts populated'

-- ==========================================================================
-- Populate Postseason Team Facts
-- ==========================================================================

\echo 'Loading postseason team data...'

-- Check if lahman.seriespost or core.seriespost exists
DO $$ 
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = 'lahman' AND table_name = 'seriespost') THEN
        INSERT INTO dw.postseason_team (year, round, lg_id, team_id, wins, losses, is_champion)
        SELECT 
            yearid AS year,
            round,
            lgidwinner AS lg_id,
            teamidwinner AS team_id,
            wins,
            losses,
            CASE WHEN round = 'WS' AND wins > losses THEN true ELSE false END AS is_champion
        FROM lahman.seriespost
        WHERE teamidwinner IS NOT NULL
        ON CONFLICT (year, round, team_id) DO UPDATE SET
            wins = EXCLUDED.wins,
            losses = EXCLUDED.losses,
            is_champion = EXCLUDED.is_champion;
        
        -- Also add losing teams
        INSERT INTO dw.postseason_team (year, round, lg_id, team_id, wins, losses, is_champion)
        SELECT 
            yearid AS year,
            round,
            lgidloser AS lg_id,
            teamidloser AS team_id,
            losses AS wins,  -- Swap for loser
            wins AS losses,
            false AS is_champion
        FROM lahman.seriespost
        WHERE teamidloser IS NOT NULL
        ON CONFLICT (year, round, team_id) DO UPDATE SET
            wins = EXCLUDED.wins,
            losses = EXCLUDED.losses,
            is_champion = EXCLUDED.is_champion;
        
        RAISE NOTICE 'Postseason team facts populated from lahman.seriespost';
    ELSIF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = 'core' AND table_name = 'seriespost') THEN
        INSERT INTO dw.postseason_team (year, round, lg_id, team_id, wins, losses, is_champion)
        SELECT 
            year_id AS year,
            round,
            lg_id_winner AS lg_id,
            team_id_winner AS team_id,
            wins,
            losses,
            CASE WHEN round = 'WS' AND wins > losses THEN true ELSE false END AS is_champion
        FROM core.seriespost
        WHERE team_id_winner IS NOT NULL
        ON CONFLICT (year, round, team_id) DO UPDATE SET
            wins = EXCLUDED.wins,
            losses = EXCLUDED.losses,
            is_champion = EXCLUDED.is_champion;
        
        -- Also add losing teams
        INSERT INTO dw.postseason_team (year, round, lg_id, team_id, wins, losses, is_champion)
        SELECT 
            year_id AS year,
            round,
            lg_id_loser AS lg_id,
            team_id_loser AS team_id,
            losses AS wins,  -- Swap for loser
            wins AS losses,
            false AS is_champion
        FROM core.seriespost
        WHERE team_id_loser IS NOT NULL
        ON CONFLICT (year, round, team_id) DO UPDATE SET
            wins = EXCLUDED.wins,
            losses = EXCLUDED.losses,
            is_champion = EXCLUDED.is_champion;
        
        RAISE NOTICE 'Postseason team facts populated from core.seriespost';
    ELSE
        RAISE NOTICE 'No seriespost table found, skipping postseason facts';
    END IF;
END $$;

-- ==========================================================================
-- Build Canonical Player-Season Table
-- ==========================================================================

\echo 'Building canonical player_season table...'

-- Create aggregated awards counts
CREATE TEMP TABLE IF NOT EXISTS temp_awards_agg AS
SELECT 
    year,
    player_id,
    COUNT(*) AS awards_total,
    SUM(CASE WHEN UPPER(award_id) LIKE '%MVP%' THEN count ELSE 0 END) AS mvp_count,
    SUM(CASE WHEN UPPER(award_id) LIKE '%CY YOUNG%' THEN count ELSE 0 END) AS cy_count,
    SUM(CASE WHEN UPPER(award_id) LIKE '%ROOKIE%' OR UPPER(award_id) LIKE '%ROY%' THEN count ELSE 0 END) AS roy_count,
    MAX(allstar_count) AS allstar_count
FROM dw.awards_season
GROUP BY year, player_id;

-- Create postseason flags per player-year
CREATE TEMP TABLE IF NOT EXISTS temp_postseason_flags AS
SELECT DISTINCT
    pt.year,
    pt.player_id,
    true AS made_postseason,
    MAX(CASE WHEN pst.round IN ('ALCS', 'NLCS', 'WS') THEN true ELSE false END) AS deep_run,
    MAX(pst.is_champion) AS is_champion
FROM dw.player_teams pt
INNER JOIN dw.postseason_team pst 
    ON pt.year = pst.year AND pt.team_id = pst.team_id
GROUP BY pt.year, pt.player_id;

-- Create average team attendance per player-year
CREATE TEMP TABLE IF NOT EXISTS temp_attendance AS
SELECT 
    pt.year,
    pt.player_id,
    AVG(t.attendance) AS avg_team_attendance
FROM dw.player_teams pt
LEFT JOIN dw.teams t ON pt.year = t.year AND pt.team_id = t.team_id
GROUP BY pt.year, pt.player_id;

-- Build the main player_season table
INSERT INTO dw.player_season (
    year,
    player_id,
    bref_id,
    name_first,
    name_last,
    country_code,
    origin_group,
    teams_played,
    was_on_roster,
    made_postseason,
    deep_run,
    is_champion,
    ab, h, hr, rbi, bb, so, sb, cs, obp, slg, ops,
    ip_outs, er, so_p, bb_p, hr_allowed, era, sv, w, l,
    awards_total, mvp_count, cy_count, roy_count, allstar_count,
    war_bat, war_pitch, war_total,
    avg_team_attendance,
    id_confidence,
    missing_fields
)
SELECT 
    pt.year,
    pt.player_id,
    p.bref_id,
    p.name_first,
    p.name_last,
    p.country_code,
    p.origin_group,
    STRING_AGG(DISTINCT pt.team_id, ', ' ORDER BY pt.team_id) AS teams_played,
    true AS was_on_roster,
    COALESCE(psf.made_postseason, false) AS made_postseason,
    COALESCE(psf.deep_run, false) AS deep_run,
    COALESCE(psf.is_champion, false) AS is_champion,
    b.ab, b.h, b.hr, b.rbi, b.bb, b.so, b.sb, b.cs, b.obp, b.slg, b.ops,
    pitch.ip_outs, pitch.er, pitch.so AS so_p, pitch.bb AS bb_p, 
    pitch.hr_allowed, pitch.era, pitch.sv, pitch.w, pitch.l,
    COALESCE(aw.awards_total, 0) AS awards_total,
    COALESCE(aw.mvp_count, 0) AS mvp_count,
    COALESCE(aw.cy_count, 0) AS cy_count,
    COALESCE(aw.roy_count, 0) AS roy_count,
    COALESCE(aw.allstar_count, 0) AS allstar_count,
    w.war_bat, w.war_pitch, w.war_total,
    att.avg_team_attendance,
    CASE 
        WHEN p.bref_id IS NOT NULL THEN 'high'
        WHEN p.player_id IS NOT NULL THEN 'medium'
        ELSE 'low'
    END AS id_confidence,
    CASE 
        WHEN b.ab IS NULL AND pitch.ip_outs IS NULL THEN 'no_stats'
        WHEN w.war_total IS NULL THEN 'no_war'
        ELSE NULL
    END AS missing_fields
FROM dw.player_teams pt
INNER JOIN dw.players p ON pt.player_id = p.player_id
LEFT JOIN dw.batting_season b ON pt.year = b.year AND pt.player_id = b.player_id
LEFT JOIN dw.pitching_season pitch ON pt.year = pitch.year AND pt.player_id = pitch.player_id
LEFT JOIN temp_awards_agg aw ON pt.year = aw.year AND pt.player_id = aw.player_id
LEFT JOIN dw.war_season w ON pt.year = w.year AND pt.player_id = w.player_id
LEFT JOIN temp_postseason_flags psf ON pt.year = psf.year AND pt.player_id = psf.player_id
LEFT JOIN temp_attendance att ON pt.year = att.year AND pt.player_id = att.player_id
GROUP BY 
    pt.year, pt.player_id, p.bref_id, p.name_first, p.name_last, 
    p.country_code, p.origin_group,
    psf.made_postseason, psf.deep_run, psf.is_champion,
    b.ab, b.h, b.hr, b.rbi, b.bb, b.so, b.sb, b.cs, b.obp, b.slg, b.ops,
    pitch.ip_outs, pitch.er, pitch.so, pitch.bb, pitch.hr_allowed, pitch.era, pitch.sv, pitch.w, pitch.l,
    aw.awards_total, aw.mvp_count, aw.cy_count, aw.roy_count, aw.allstar_count,
    w.war_bat, w.war_pitch, w.war_total,
    att.avg_team_attendance
ON CONFLICT (year, player_id) DO UPDATE SET
    bref_id = EXCLUDED.bref_id,
    name_first = EXCLUDED.name_first,
    name_last = EXCLUDED.name_last,
    country_code = EXCLUDED.country_code,
    origin_group = EXCLUDED.origin_group,
    teams_played = EXCLUDED.teams_played,
    was_on_roster = EXCLUDED.was_on_roster,
    made_postseason = EXCLUDED.made_postseason,
    deep_run = EXCLUDED.deep_run,
    is_champion = EXCLUDED.is_champion,
    ab = EXCLUDED.ab, h = EXCLUDED.h, hr = EXCLUDED.hr, rbi = EXCLUDED.rbi,
    bb = EXCLUDED.bb, so = EXCLUDED.so, sb = EXCLUDED.sb, cs = EXCLUDED.cs,
    obp = EXCLUDED.obp, slg = EXCLUDED.slg, ops = EXCLUDED.ops,
    ip_outs = EXCLUDED.ip_outs, er = EXCLUDED.er, so_p = EXCLUDED.so_p,
    bb_p = EXCLUDED.bb_p, hr_allowed = EXCLUDED.hr_allowed, era = EXCLUDED.era,
    sv = EXCLUDED.sv, w = EXCLUDED.w, l = EXCLUDED.l,
    awards_total = EXCLUDED.awards_total, mvp_count = EXCLUDED.mvp_count,
    cy_count = EXCLUDED.cy_count, roy_count = EXCLUDED.roy_count,
    allstar_count = EXCLUDED.allstar_count,
    war_bat = EXCLUDED.war_bat, war_pitch = EXCLUDED.war_pitch, war_total = EXCLUDED.war_total,
    avg_team_attendance = EXCLUDED.avg_team_attendance,
    id_confidence = EXCLUDED.id_confidence,
    missing_fields = EXCLUDED.missing_fields;

\echo 'Player_season table populated'

-- ==========================================================================
-- Summary Statistics
-- ==========================================================================

\echo ''
\echo '==================================================================='
\echo 'Fact Tables Summary'
\echo '==================================================================='

SELECT 'Player-Teams' AS fact_table, COUNT(*) AS record_count FROM dw.player_teams
UNION ALL
SELECT 'Batting Season' AS fact_table, COUNT(*) AS record_count FROM dw.batting_season
UNION ALL
SELECT 'Pitching Season' AS fact_table, COUNT(*) AS record_count FROM dw.pitching_season
UNION ALL
SELECT 'Fielding Season' AS fact_table, COUNT(*) AS record_count FROM dw.fielding_season
UNION ALL
SELECT 'Awards Season' AS fact_table, COUNT(*) AS record_count FROM dw.awards_season
UNION ALL
SELECT 'WAR Season' AS fact_table, COUNT(*) AS record_count FROM dw.war_season
UNION ALL
SELECT 'Postseason Team' AS fact_table, COUNT(*) AS record_count FROM dw.postseason_team
UNION ALL
SELECT 'Player Season (Canonical)' AS fact_table, COUNT(*) AS record_count FROM dw.player_season
ORDER BY fact_table;

\echo ''
\echo 'Player Season Completeness:'
SELECT 
    CASE 
        WHEN missing_fields IS NULL THEN 'Complete'
        ELSE missing_fields
    END AS data_status,
    COUNT(*) AS record_count,
    ROUND(100.0 * COUNT(*) / SUM(COUNT(*)) OVER (), 2) AS percentage
FROM dw.player_season
GROUP BY missing_fields
ORDER BY record_count DESC;

\echo ''
\echo 'Fact tables built successfully!'
