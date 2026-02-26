<?php
session_start();
if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'){
    header("Location: login.php"); exit;
}
include('db.php');
$page_title = 'แดชบอร์ด Admin';

$total_books   = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM books"))['c'];
$total_members = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM users WHERE first_name != 'admin'"))['c'];
$total_borrow  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM borrow_history WHERE status='borrowed'"))['c'];
$total_overdue = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM borrow_history WHERE status='overdue'"))['c'];
$unpaid_fines  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COALESCE(SUM(price),0) s FROM fines"))['s'];

// Auto update overdue (30 วัน)
mysqli_query($conn,"UPDATE borrow_history SET status='overdue' WHERE status='borrowed' AND borrow_date < DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND return_date IS NULL");

$recent = mysqli_query($conn,"
    SELECT bh.history_id, bh.borrow_date, bh.return_date, bh.status,
           bk.book_name, CONCAT(u.first_name,' ',u.last_name) AS fullname
    FROM borrow_history bh
    JOIN books bk ON bh.book_id=bk.book_id
    JOIN users u ON bh.user_id=u.user_id
    ORDER BY bh.history_id DESC LIMIT 8
");
include('header.php');
?>
<div class="page-header">
  <div class="page-header-inner">
    <div class="page-title"><i class="fas fa-tachometer-alt"></i> แดชบอร์ดผู้ดูแลระบบ</div>
    <a href="borrow_manage.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> บันทึกการยืม</a>
  </div>
</div>
<div class="container">
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon" style="background:#eef2ff"><span style="font-size:1.4rem">📚</span></div>
      <div><div class="stat-value" style="color:#4f46e5"><?= $total_books ?></div><div class="stat-label">หนังสือทั้งหมด</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:#dcfce7"><span style="font-size:1.4rem">👥</span></div>
      <div><div class="stat-value" style="color:#16a34a"><?= $total_members ?></div><div class="stat-label">สมาชิกทั้งหมด</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:#dbeafe"><span style="font-size:1.4rem">📖</span></div>
      <div><div class="stat-value" style="color:#2563eb"><?= $total_borrow ?></div><div class="stat-label">กำลังยืมอยู่</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:#fee2e2"><span style="font-size:1.4rem">⚠️</span></div>
      <div><div class="stat-value" style="color:#dc2626"><?= $total_overdue ?></div><div class="stat-label">เกินกำหนด</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:#fef9c3"><span style="font-size:1.4rem">💰</span></div>
      <div><div class="stat-value" style="color:#d97706">฿<?= number_format($unpaid_fines,2) ?></div><div class="stat-label">ค่าปรับรวม</div></div>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fas fa-clock" style="color:#4f46e5"></i> การยืมล่าสุด</div>
      <a href="borrow_manage.php" class="btn btn-outline btn-sm">ดูทั้งหมด</a>
    </div>
    <div class="table-wrap">
    <table class="tbl">
      <thead><tr><th>#</th><th>ชื่อหนังสือ</th><th>ผู้ยืม</th><th>วันที่ยืม</th><th>วันที่คืน</th><th>สถานะ</th><th>จัดการ</th></tr></thead>
      <tbody>
      <?php while($r = mysqli_fetch_assoc($recent)): ?>
      <?php $badge = match($r['status']){
        'borrowed'=>'<span class="badge badge-blue"><i class="fas fa-book-open"></i> กำลังยืม</span>',
        'overdue' =>'<span class="badge badge-red"><i class="fas fa-exclamation-circle"></i> เกินกำหนด</span>',
        'returned'=>'<span class="badge badge-green"><i class="fas fa-check-circle"></i> คืนแล้ว</span>',
        default   =>'<span class="badge badge-gray">'.$r['status'].'</span>'
      }; ?>
      <tr class="<?= $r['status']==='overdue'?'overdue-row':'' ?>">
        <td class="text-muted"><?= $r['history_id'] ?></td>
        <td><strong><?= htmlspecialchars($r['book_name']) ?></strong></td>
        <td><?= htmlspecialchars($r['fullname']) ?></td>
        <td class="text-muted"><?= date('d/m/Y',strtotime($r['borrow_date'])) ?></td>
        <td class="text-muted"><?= $r['return_date'] ? date('d/m/Y',strtotime($r['return_date'])) : '–' ?></td>
        <td><?= $badge ?></td>
        <td><a href="borrow_manage.php?edit=<?= $r['history_id'] ?>" class="btn btn-warning btn-xs"><i class="fas fa-edit"></i> แก้ไข</a></td>
      </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
    </div>
  </div>
</div>
</body></html>
