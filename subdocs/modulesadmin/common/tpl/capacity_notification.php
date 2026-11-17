<p><?php echo SYS_NAME ?> 使用容量についてお知らせします。</p>

<p><?php echo date('Y年n月j日') ?> 8時時点のフォルダの使用容量</p>
<?php if ($personalTotalCapacity !== ''): ?>
    <li>個人フォルダ：<?php echo $personalTotalCapacity ?? '0' ?>B</li>
<?php endif; ?>
<?php if ($shareTotalCapacity !== ''): ?>
    <li>共有フォルダ：<?php echo $shareTotalCapacity  ?? '0' ?>B</li>
<?php endif; ?>

<?php foreach ($capacityTableHtml as $capacityTable):
    echo $capacityTable;
endforeach; ?>

<hr />
<p>本メールは使用容量通知に関する設定に基づき配信しています。</p>
<p>本メールの配信を停止したい場合は管理者画面のログイン後、「各種設定>その他設定>使用容量通知に関する設定>使用状況通知設定」にあるチェックボックスを外してください。</p>