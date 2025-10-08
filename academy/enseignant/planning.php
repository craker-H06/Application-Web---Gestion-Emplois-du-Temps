<style>
    .navbar-brand {
        font-size: 1.5rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
    }
    .navbar-brand .logo-text {
        display: flex;
        flex-direction: column;
        line-height: 1.2;
    }
    .navbar-brand .main-text {
        font-size: 1.5rem;
        font-weight: 700;
        color: white;
    }
    .navbar-brand .sub-text {
        font-size: 0.9rem;
        font-weight: 400;
        color: rgba(255, 255, 255, 0.9);
    }
    .navbar-brand img {
        height: 40px;
        width: auto;
    }
</style>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="../index.php">
            <img src="../assets/images/logo.png" alt="Logo ASCENCIA">
            <div class="logo-text">
                <span class="main-text">ASCENCIA</span>
                <span class="sub-text">business school</span>
            </div>
        </a>
        // ... existing code ...
    </div>
</nav> 