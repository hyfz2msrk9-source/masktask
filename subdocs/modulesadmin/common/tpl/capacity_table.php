<h4><?php echo $folderType ?>使用容量の推移</h4>
<p>保存容量（MB）</p>
<table style="margin-left:10px; font-size:small; border-collapse:collapse; border:solid 1px #999999;">
    <tr style="padding:3px; border:solid 1px #999999;">
        <?php foreach ($weekCapacities as $thisWeekCapacity): ?>
            <th style="padding:3px; border:solid 1px #999999; background-color:#EEEEEE;"><?php echo htmlspecialchars($thisWeekCapacity["save_dt"]); ?></th>
        <?php endforeach; ?>
    </tr>
    <tr style="padding:3px; border:solid 1px #999999;">
        <?php foreach ($weekCapacities as $thisWeekCapacity): ?>
            <td style="padding:3px; border:solid 1px #999999; text-align:center; vertical-align:middle;"><?php echo htmlspecialchars($thisWeekCapacity['usage_size']) ?? '-'; ?></td>
        <?php endforeach; ?>
    </tr>
</table>
<p>保存容量の推移（MB）</p>
<table style="font-size:small; margin-left:10px; border-collapse:collapse; border:solid 1px #999999;">
    <thead>
        <tr style="padding:3px; border:solid 1px #999999;">
            <th style="padding:3px; border:solid 1px #999999;"></th>
            <?php foreach (array_keys($monthCapacityByYears) as $year): ?>
                <th style="padding:3px; border:solid 1px #999999; background-color:#EEEEEE;"><?php echo htmlspecialchars($year); ?></th>
            <?php endforeach; ?>
        </tr>
    </thead>
    <?php foreach ($headerMonths as $headerMonth): ?>
        <tr style="padding:3px; border:solid 1px #999999;">
            <th style="padding:3px; border:solid 1px #999999; background-color:#EEEEEE;"><?php echo htmlspecialchars($headerMonth); ?></th>
            <?php foreach ($monthCapacityByYears as $year => $monthCapacity): ?>
                <?php
                if (!isset($monthCapacity[$headerMonth])) {
                    echo '<td style="padding:3px; border:solid 1px #999999; text-align:center;">' . '-';
                } else {
                    echo '<td style="padding:3px; border:solid 1px #999999; text-align:right;">' . htmlspecialchars($monthCapacity[$headerMonth] ?? '');
                }
                ?>
                </td>
            <?php endforeach; ?>
        </tr>
    <?php endforeach; ?>
</table>