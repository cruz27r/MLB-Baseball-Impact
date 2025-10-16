SELECT ps.year,
       ps.origin_group,
       AVG(s.salary_usd) AS avg_salary_usd,
       AVG(ps.war_total) AS avg_war,
       AVG(s.salary_usd / NULLIF(ps.war_total,0)) AS avg_cost_per_war
FROM dw.player_season ps
JOIN dw.salaries s USING (year, player_id)
GROUP BY ps.year, ps.origin_group
ORDER BY ps.year, ps.origin_group;
