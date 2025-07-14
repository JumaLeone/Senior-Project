<div class="container mt-5">
    <div class="alert alert-danger">
        <h4>Payment Failed</h4>
        <p><?= htmlspecialchars($_GET['error']) ?></p>
    </div>
    <a href="javascript:history.back()" class="btn btn-primary">Try Again</a>
</div>