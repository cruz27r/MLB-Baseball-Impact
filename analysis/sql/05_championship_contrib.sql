SELECT year, origin_group,
       war_on_contenders, war_on_champions
FROM dw.mv_championship_contrib
ORDER BY year, origin_group;
