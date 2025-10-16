SELECT year,
       total_players,
       us_players, latin_players, other_players,
       us_share, latin_share, other_share
FROM dw.mv_yearly_composition
ORDER BY year;
