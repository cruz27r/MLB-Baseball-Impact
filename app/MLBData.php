<?php
/**
 * CS437 MLB Global Era - MLB Data Handler
 * 
 * Provides methods for querying MLB data from materialized views.
 */

require_once __DIR__ . '/db.php';

class MLBData {
    private $db;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get team composition data
     * 
     * @param int|null $year Filter by year
     * @param string|null $team Filter by team name
     * @return array Team composition data
     */
    public function getTeamComposition($year = null, $team = null) {
        $sql = "SELECT * FROM mv_team_composition WHERE 1=1";
        $params = [];
        
        if ($year !== null) {
            $sql .= " AND year = :year";
            $params[':year'] = $year;
        }
        
        if ($team !== null) {
            $sql .= " AND team_name ILIKE :team";
            $params[':team'] = "%{$team}%";
        }
        
        $sql .= " ORDER BY year DESC, foreign_player_percentage DESC LIMIT 100";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Get awards data for foreign players
     * 
     * @param int|null $year Filter by year
     * @param string|null $country Filter by country
     * @param string|null $awardType Filter by award type
     * @return array Awards data
     */
    public function getAwardsIndex($year = null, $country = null, $awardType = null) {
        $sql = "SELECT * FROM mv_foreign_awards WHERE 1=1";
        $params = [];
        
        if ($year !== null) {
            $sql .= " AND award_year = :year";
            $params[':year'] = $year;
        }
        
        if ($country !== null) {
            $sql .= " AND birth_country ILIKE :country";
            $params[':country'] = "%{$country}%";
        }
        
        if ($awardType !== null) {
            $sql .= " AND award_type ILIKE :award_type";
            $params[':award_type'] = "%{$awardType}%";
        }
        
        $sql .= " ORDER BY award_year DESC, award_count DESC LIMIT 100";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Get statistical leaders data
     * 
     * @param int|null $year Filter by year
     * @param string|null $country Filter by country
     * @param string $category Category (batting, pitching)
     * @param int $limit Number of results to return
     * @return array Leaders data
     */
    public function getLeadersIndex($year = null, $country = null, $category = 'batting', $limit = 50) {
        $sql = "SELECT * FROM mv_statistical_leaders WHERE 1=1";
        $params = [];
        
        if ($year !== null) {
            $sql .= " AND year = :year";
            $params[':year'] = $year;
        }
        
        if ($country !== null) {
            $sql .= " AND birth_country ILIKE :country";
            $params[':country'] = "%{$country}%";
        }
        
        // Order by category
        if ($category === 'batting') {
            $sql .= " ORDER BY batting_average DESC";
        } else if ($category === 'home_runs') {
            $sql .= " ORDER BY home_runs DESC";
        } else if ($category === 'rbi') {
            $sql .= " ORDER BY rbi DESC";
        } else {
            $sql .= " ORDER BY year DESC";
        }
        
        $sql .= " LIMIT :limit";
        $params[':limit'] = min($limit, 100); // Cap at 100
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Get foreign players summary
     * 
     * @param string|null $country Filter by country
     * @param int|null $yearStart Start year
     * @param int|null $yearEnd End year
     * @return array Summary data
     */
    public function getForeignPlayersSummary($country = null, $yearStart = null, $yearEnd = null) {
        $sql = "SELECT * FROM mv_foreign_players_summary WHERE 1=1";
        $params = [];
        
        if ($country !== null) {
            $sql .= " AND birth_country ILIKE :country";
            $params[':country'] = "%{$country}%";
        }
        
        if ($yearStart !== null) {
            $sql .= " AND birth_year >= :year_start";
            $params[':year_start'] = $yearStart;
        }
        
        if ($yearEnd !== null) {
            $sql .= " AND birth_year <= :year_end";
            $params[':year_end'] = $yearEnd;
        }
        
        $sql .= " ORDER BY birth_year DESC, player_count DESC LIMIT 100";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Get available countries
     * 
     * @return array List of countries
     */
    public function getCountries() {
        $sql = "SELECT DISTINCT birth_country 
                FROM mv_foreign_players_summary 
                ORDER BY birth_country";
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Get available years range
     * 
     * @return array Min and max years
     */
    public function getYearsRange() {
        $sql = "SELECT 
                    MIN(birth_year) as min_year,
                    MAX(birth_year) as max_year
                FROM mv_foreign_players_summary";
        
        return $this->db->fetchOne($sql);
    }
}
?>
