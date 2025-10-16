SELECT year, origin_group, roster_share, war_share, impact_index
FROM dw.mv_impact_index
ORDER BY year, origin_group;
