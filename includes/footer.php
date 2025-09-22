
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Professional Notification System -->
    <script src="js/notifications.js"></script>
    
    <!-- Custom JS -->
    <script>
        // Initialize DataTables
        $(document).ready(function() {
            $('.data-table').DataTable({
                "pageLength": 25,
                "responsive": true,
                "language": {
                    "search": "Search:",
                    "lengthMenu": "Show _MENU_ entries",
                    "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                    "paginate": {
                        "first": "First",
                        "last": "Last",
                        "next": "Next",
                        "previous": "Previous"
                    }
                }
            });
        });
        
        // Mobile sidebar toggle
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('show');
        }
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.querySelector('.sidebar');
            const toggleBtn = document.querySelector('.navbar-toggler');
            
            if (window.innerWidth <= 768 && 
                !sidebar.contains(event.target) && 
                !toggleBtn.contains(event.target)) {
                sidebar.classList.remove('show');
            }
        });
        
        // Confirm delete actions
        function confirmDelete(message = 'Are you sure you want to delete this item?') {
            return confirm(message);
        }
        
        // Show loading spinner
        function showLoading() {
            const spinner = '<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
            document.body.insertAdjacentHTML('beforeend', spinner);
        }
        
        // Hide loading spinner
        function hideLoading() {
            const spinner = document.querySelector('.spinner-border');
            if (spinner) {
                spinner.parentElement.remove();
            }
        }
    </script>
    
</body>
</html>
