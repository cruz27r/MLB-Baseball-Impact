SELECT year, origin_group,
       awards_total, mvp, cy, roy, allstar_total
FROM dw.mv_awards_share
ORDER BY year, origin_group;
