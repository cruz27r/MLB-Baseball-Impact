<?php
/**
 * CS437 MLB Global Era - Table Partial
 * 
 * Reusable table component for displaying data.
 */

/**
 * Render a data table
 * 
 * @param array $headers Array of column headers
 * @param array $rows Array of data rows
 * @param string $tableClass Optional CSS class for the table
 */
function renderTable($headers = [], $rows = [], $tableClass = 'data-table') {
    if (empty($headers)) {
        $headers = ['Player', 'Country', 'Year', 'Team', 'Stats'];
    }
    
    if (empty($rows)) {
        $rows = [
            ['Sample Player', 'Dominican Republic', '2023', 'Sample Team', 'Sample Stats']
        ];
    }
    ?>
    <div class="table-wrapper">
        <table class="<?php echo htmlspecialchars($tableClass); ?>">
            <thead>
                <tr>
                    <?php foreach ($headers as $header): ?>
                        <th><?php echo htmlspecialchars($header); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <?php foreach ($row as $cell): ?>
                            <td><?php echo htmlspecialchars($cell); ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// Call the function to display default table
renderTable();
?>
