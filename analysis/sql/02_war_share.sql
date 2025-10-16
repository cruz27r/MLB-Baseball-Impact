SELECT year, origin_group, total_war, players, avg_war, war_share
FROM dw.mv_war_by_origin
ORDER BY year, origin_group;
