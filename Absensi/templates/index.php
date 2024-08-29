<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Attendance Records</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:400,500,700&display=swap">
    <style>
        
            body {
                font-family: 'Roboto', sans-serif;
                background: #f7f9fc; /* Subtle background color */
            }

            table {
                width: 100%;
                margin-top: 20px;
            }

            table thead th {
                position: sticky;
                top: 0;
                background-color: #f8f9fa;
                z-index: 1;
            }

            table tbody tr:hover {
                background-color: #f1f1f1;
            }

            table th, table td {
                padding: 12px 15px;
                text-align: center;
            }

            .table-bordered {
                border: 1px solid #dee2e6;
            }

            .table-bordered th,
            .table-bordered td {
                border: 1px solid #dee2e6;
            }


            .modal-backdrop {
                z-index: 1040 !important;
            }

            .modal-content {
                margin: 2px auto;
                z-index: 1100 !important;
            }
            .filter-form {
                display: flex;
                flex-wrap: wrap;
                gap: 15px;
                margin-bottom: 20px;
            }

            .filter-form .form-group {
                flex: 1 1 200px;
                margin-right: 10px;
            }

            .filter-form button {
                align-self: flex-end;
                margin-right: 10px;
            }
            .container {
                padding: 20px;
                margin-top: 80px; /* Adjust depending on the height of the navbar */
            }

            .navbar {
                position: fixed;
                top: 0;
                width: 100%;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                z-index: 1000;
            }

            .modal-dialog {
                max-width: 500px;
                margin: 1.75rem auto;
            }

            .modal-content {
                padding: 20px;
            }

            .modal-header {
                border-bottom: none;
            }

            .modal-footer {
                border-top: none;
                justify-content: center;
            }

            .btn-primary {
                background-color: #007bff;
                border-color: #007bff;
            }

            .btn-primary:hover {
                background-color: #0056b3;
                border-color: #004085;
            }

            .btn-secondary {
                background-color: #6c757d;
                border-color: #6c757d;
            }

            .btn-secondary:hover {
                background-color: #5a6268;
                border-color: #545b62;
            }

    </style>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        var navbarHeight = document.querySelector('.navbar').offsetHeight;
        document.body.style.paddingTop = navbarHeight + 'px';
    });
    </script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <a class="navbar-brand" href="#">
            <img src="img/logo.png" alt="Company Logo" style="height: 40px; margin-right: 10px;">
            Attendance System
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.html">Dashboard</a>
                </li>
                <li class="nav-item active">
                    <a class="nav-link" href="index.php">Manage Attendances</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="manage_users.html">Manage Users</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="form.html">Upload File</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container mt-4">
        <h1>Manage Attendance Records</h1>

        <div class="btn-container mb-4">
            <!-- Export button that opens the modal -->
            <button class="btn btn-success" data-toggle="modal" data-target="#exportModal">Export to Excel</button>
            <button class="btn btn-danger" data-toggle="modal" data-target="#deleteModal">Delete Records</button>
        </div>

        <form class="filter-form" onsubmit="fetchRecords(); return false;">
            <div class="form-group">
                <label for="idFilter">No :</label>
                <input type="text" id="idFilter" class="form-control">
            </div>
            <div class="form-group">
                <label for="userIdFilter">User ID :</label>
                <input type="text" id="userIdFilter" class="form-control">
            </div>
            <div class="form-group">
                <label for="nameFilter">Name :</label>
                <input type="text" id="nameFilter" class="form-control">
            </div>
            <div class="form-group">
                <label for="attendanceFilter">Attendance Type :</label>
                <select id="attendanceFilter" class="form-control">
                    <option value="">All</option>
                    <option value="0">-</option>
                    <option value="1">Present</option>
                    <option value="2">Izin</option>
                    <option value="3">Sakit</option>
                    <option value="4">Cuti</option>
                </select>
            </div>
            <div class="form-group">
                <label for="lateFilter">Late Status :</label>
                <select id="lateFilter" class="form-control">
                    <option value="">All</option>
                    <option value="late">Late</option>
                    <option value="on-time">On Time</option>
                    <option value="early-leave">Early Leave</option>
                </select>
            </div>
            <div class="form-group">
                <label for="start_date">Start Date:</label>
                <input type="date" id="start_date" class="form-control">
            </div>
            <div class="form-group">
                <label for="end_date">End Date:</label>
                <input type="date" id="end_date" class="form-control">
            </div>
            <button type="submit" class="btn btn-primary">Filter</button>
            <button type="button" class="btn btn-secondary" onclick="clearFilters()">Clear Filter</button>
        </form>
        <div id="records"></div>
    </div>

    <!-- Export Modal -->
    <div class="modal fade" id="exportModal" tabindex="-1" role="dialog" aria-labelledby="exportModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exportModalLabel">Select Month to Export</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="../export.php" method="get">
                        <div class="form-group">
                            <label for="year">Year:</label>
                            <select id="year" name="year" class="form-control">
                                <?php
                                // Generate year options for the past 10 years and the current year
                                $currentYear = date('Y');
                                for ($i = $currentYear - 10; $i <= $currentYear; $i++) {
                                    echo "<option value=\"$i\" " . ($i == $currentYear ? 'selected' : '') . ">$i</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="month">Month:</label>
                            <select id="month" name="month" class="form-control">
                                <?php
                                // Generate month options
                                for ($i = 1; $i <= 12; $i++) {
                                    $monthName = date('F', mktime(0, 0, 0, $i, 1));
                                    echo "<option value=\"" . str_pad($i, 2, '0', STR_PAD_LEFT) . "\" " . ($i == date('n') ? 'selected' : '') . ">$monthName</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Export Data</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Records Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Delete Records</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Select the type of records you want to delete:</p>
                    <button class="btn btn-danger" onclick="deleteRecords('today')">Delete Today's Records</button>
                    <button class="btn btn-danger" onclick="deleteRecords('all')">Delete All Records</button>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', fetchRecords);
        function applyFilters() {
            // Get filter values from inputs
            const idFilter = document.getElementById('idFilter').value || '';
            const userIdFilter = document.getElementById('userIdFilter').value || '';
            const attendanceStatusFilter = document.getElementById('attendanceFilter').value || '';
            const nameFilter = document.getElementById('nameFilter').value || '';
            const lateFilter = document.getElementById('lateFilter').value || '';
            const startDate = document.getElementById('start_date').value || '';  // Corrected ID
            const endDate = document.getElementById('end_date').value || '';      // Corrected ID

            // Build query string parameters
            const queryString = new URLSearchParams({
                id: idFilter,
                user_id: userIdFilter,
                attendance_status: attendanceStatusFilter,
                name: nameFilter,
                is_late: lateFilter,
                start_date: startDate,
                end_date: endDate
            }).toString();

            // Fetch filtered records from backend
            fetch(`../fetch_records.php?${queryString}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    // Check if data is retrieved and passed correctly
                    console.log('Fetched data:', data);

                    // Display the records
                    displayRecords(data);
                })
                .catch(error => {
                    console.error('Error fetching or parsing records:', error);
                    document.getElementById('records').innerHTML = '<div class="alert alert-danger">An error occurred while fetching records.</div>';
                });
        }

        function clearFilters() {
            // Reset the filter input fields
            document.getElementById('idFilter').value = '';
            document.getElementById('userIdFilter').value = '';
            document.getElementById('attendanceFilter').value = '';
            document.getElementById('nameFilter').value = '';
            document.getElementById('lateFilter').value = '';
            document.getElementById('start_date').value = '';
            document.getElementById('end_date').value = '';

            // Fetch all records without filters
            fetchRecords();
        }


        // Function to fetch all records (used on initial page load without filters)
        function fetchRecords() {
            // Build query string parameters, but only include those which have values
            const queryString = new URLSearchParams();

            const idFilter = document.getElementById('idFilter').value;
            const userIdFilter = document.getElementById('userIdFilter').value;
            const attendanceStatusFilter = document.getElementById('attendanceFilter').value;
            const nameFilter = document.getElementById('nameFilter').value;
            const lateFilter = document.getElementById('lateFilter').value;
            const startDate = document.getElementById('start_date').value;  // Corrected ID
            const endDate = document.getElementById('end_date').value;      // Corrected ID

            // Add parameters to queryString only if they have values
            if (idFilter) queryString.append('id', idFilter);
            if (userIdFilter) queryString.append('user_id', userIdFilter);
            if (attendanceStatusFilter) queryString.append('attendance_status', attendanceStatusFilter);
            if (nameFilter) queryString.append('name', nameFilter);
            if (lateFilter) queryString.append('is_late', lateFilter);
            if (startDate) queryString.append('start_date', startDate);
            if (endDate) queryString.append('end_date', endDate);

            // Check if there are any filters; if not, fetch all records
            const queryURL = queryString.toString() ? `../fetch_records.php?${queryString}` : '../fetch_records.php';

            // Fetch records from the backend (with or without filters)
            fetch(queryURL)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Fetched data:', data);
                    displayRecords(data);
                })
                .catch(error => {
                    console.error('Error fetching records:', error);
                    document.getElementById('records').innerHTML = '<div class="alert alert-danger">An error occurred while fetching records.</div>';
                });
        }

        function displayRecords(data) {
            const recordsDiv = document.getElementById('records');
            let table = '';

            // Group data by user_id and date (without time)
            const groupedData = {};
            data.forEach(record => {
                const user_id = record.user_id;
                const dateOnly = record.datetime.split(' ')[0]; // Extract only the date part
            
                // Create group if not exists
                if (!groupedData[user_id]) {
                    groupedData[user_id] = {};
                }
                if (!groupedData[user_id][dateOnly]) {
                    groupedData[user_id][dateOnly] = {
                        user_id: record.user_id,
                        full_name: record.full_name,
                        check_in: null,
                        check_out: null,
                        attendance_status: 'Absent',
                        is_late_in: '-',
                        is_early_leave: '-'
                    };
                }
            
                // Set check_in or check_out based on check_type
                if (record.check_type == 0) { // Check In
                    groupedData[user_id][dateOnly].check_in = record.datetime;
                    groupedData[user_id][dateOnly].is_late_in = isLate(new Date(record.datetime));
                } else if (record.check_type == 1) { // Check Out
                    groupedData[user_id][dateOnly].check_out = record.datetime;
                }
            });
            
            // Iterate over grouped data and determine attendance status
            let counter = 1;
            Object.keys(groupedData).forEach(user_id => {
                Object.keys(groupedData[user_id]).forEach(dateOnly => {
                    const record = groupedData[user_id][dateOnly];
            
                    // Determine attendance status based on check-in and check-out presence
                    if (record.check_in && record.check_out) {
                        record.attendance_status = 'Present';
                        // Check if the user left early
                        const checkInTime = new Date(record.check_in);
                        const checkOutTime = new Date(record.check_out);
                        const endOfWorkDay = new Date(checkInTime.getFullYear(), checkInTime.getMonth(), checkInTime.getDate(), 17, 0); // Assuming 5:00 PM as the end of workday
                        record.is_early_leave = checkOutTime < endOfWorkDay ? 'Early Leave' : '-';
                    } else if (record.check_in) {
                        record.attendance_status = 'Belum Checkout';
                    } else if (record.check_out) {
                        record.attendance_status = 'Belum Checkin';
                    } else {
                        record.attendance_status = 'Absent';
                    }
            
                    // Create table row for each record
                    table += `<tr>
                        <td>${counter}</td>
                        <td>${record.user_id}</td> <!-- Now the user_id field is non-editable -->
                        <td>${record.full_name}</td>
                        <td><input type="datetime-local" value="${record.check_in ? record.check_in.replace(' ', 'T') : ''}" id="datetime_${counter}_in" class="form-control datetimepicker"></td>
                        <td><input type="datetime-local" value="${record.check_out ? record.check_out.replace(' ', 'T') : ''}" id="datetime_${counter}_out" class="form-control datetimepicker"></td>
                        <td>
                            <select id="attendance_status_${counter}" class="form-control">
                                <option value="0" ${record.attendance_status === 'Absent' ? 'selected' : ''}>Absent</option>
                                <option value="1" ${record.attendance_status === 'Present' ? 'selected' : ''}>Present</option>
                                <option value="2" ${record.attendance_status === 'Izin' ? 'selected' : ''}>Izin</option>
                                <option value="3" ${record.attendance_status === 'Sakit' ? 'selected' : ''}>Sakit</option>
                                <option value="4" ${record.attendance_status === 'Cuti' ? 'selected' : ''}>Cuti</option>
                                <option value="5" ${record.attendance_status === 'Belum Checkout' ? 'selected' : ''}>Belum Checkout</option>
                                <option value="6" ${record.attendance_status === 'Belum Checkin' ? 'selected' : ''}>Belum Checkin</option>
                            </select>
                        </td>
                        <td>${record.is_late_in} / ${record.is_early_leave}</td>
                        <td>
                            <button onclick="saveRecord(${record.check_in?.id || 'null'}, ${record.check_out?.id || 'null'})" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                            </button>
                        </td>
                    </tr>`;
                    counter++;
                });
            });
            
            // If no data, display empty message
            if (counter === 1) {
                table += '<tr><td colspan="8" class="text-center">No records found.</td></tr>';
            }
            
            // Wrap table with HTML table elements and set consistent column widths using inline CSS
            table = `<table class="table table-bordered">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th style="width: 50px;">User ID</th> <!-- Matching width for User ID and No -->
                        <th>Full Name</th>
                        <th>Check In</th>
                        <th>Check Out</th>
                        <th>Attendance Status</th>
                        <th>Late Status (In / Out)</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    ${table}
                </tbody>
            </table>`;
            
            // Display table inside records div
            recordsDiv.innerHTML = table;
            
            // Reinitialize datepicker after table is rendered
            flatpickr('.datetimepicker', {
                enableTime: true,
                dateFormat: 'Y-m-d H:i',
            });
        }


        function isLate(datetime) {
            const checkInThresholdHour = 9;
            const checkInThresholdMinute = 30;
            
            const date = new Date(datetime);
            const thresholdTime = new Date(date.getFullYear(), date.getMonth(), date.getDate(), checkInThresholdHour, checkInThresholdMinute);
            
            return date > thresholdTime ? 'Late' : 'On Time';
        }

        function checkEarlyLeave(checkOutTime) {
            const workEndTime = new Date(checkOutTime);
            workEndTime.setHours(17, 0, 0, 0); // Set the work end time (5:00 PM)
            return checkOutTime < workEndTime ? 'Early Leave' : '-';
        }
                
function saveRecord(checkInId, checkOutId) {
    const row = event.target.closest('tr');
    const userId = row.querySelector('td:nth-child(2)').textContent.trim(); // Get the user_id from the second column
    const datetimeIn = row.querySelector('input[id^="datetime_"][id$="_in"]').value;  // Match input with id ending in '_in'
    const datetimeOut = row.querySelector('input[id^="datetime_"][id$="_out"]').value; // Match input with id ending in '_out'
    const attendance = row.querySelector('select').value;

    // Create Check In record if CheckInId is empty and datetimeIn is provided
    if (!checkInId && datetimeIn) {
        const dataIn = {
            user_id: userId,
            datetime: datetimeIn,
            attendance_status: attendance,
            check_type: 0,
            action: 'create'
        };
        console.log(dataIn);

        fetch('../manage_record.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(dataIn)
        })
        .then(response => response.json())
        .then(result => {
            if (result.status === 'success') {
                alert('Check In record created successfully.');
            } else {
                alert('Failed to create Check In record: ' + (result.message || 'Unknown error'));
            }
        })
        .catch(error => console.error('Error creating Check In record:', error));
    }

    // Create Check Out record if CheckOutId is empty and datetimeOut is provided
    if (!checkOutId && datetimeOut) {
        const dataOut = {
            user_id: userId,
            datetime: datetimeOut,
            attendance_status: attendance,
            check_type: 1,
            action: 'create'
        };
        console.log(dataOut);

        fetch('../manage_record.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(dataOut)
        })
        .then(response => response.json())
        .then(result => {
            if (result.status === 'success') {
                alert('Check Out record created successfully.');
            } else {
                alert('Failed to create Check Out record: ' + (result.message || 'Unknown error'));
            }
        })
        .catch(error => console.error('Error creating Check Out record:', error));
    }

    // Update Check In record if CheckInId exists
    if (checkInId) {
        const dataIn = {
            id: checkInId,
            user_id: userId,
            datetime: datetimeIn,
            attendance_status: attendance,
            check_type: 0,
            action: 'edit'
        };

        fetch('../manage_record.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(dataIn)
        })
        .then(response => response.json())
        .then(result => {
            if (result.status === 'success') {
                alert('Check In record saved successfully.');
            } else {
                alert('Failed to save Check In record: ' + (result.message || 'Unknown error'));
            }
        })
        .catch(error => console.error('Error saving Check In record:', error));
    }

    // Update Check Out record if CheckOutId exists
    if (checkOutId) {
        const dataOut = {
            id: checkOutId,
            user_id: userId,
            datetime: datetimeOut,
            attendance_status: attendance,
            check_type: 1,
            action: 'edit'
        };

        fetch('../manage_record.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(dataOut)
        })
        .then(response => response.json())
        .then(result => {
            if (result.status === 'success') {
                alert('Check Out record saved successfully.');
            } else {
                alert('Failed to save Check Out record: ' + (result.message || 'Unknown error'));
            }
        })
        .catch(error => console.error('Error saving Check Out record:', error));
    }
}


        function deleteRecords(type) {
            if (!confirm('Are you sure you want to delete all records?')) {
                return; // Exit if user cancels the action
            }
            let formData = new FormData();
            formData.append('action', 'delete_all');
            formData.append('type', type);
                
            fetch('../manage_attendance.php', {
                method: 'POST',
                body: formData
                })
                .then(response => response.text())
                .then(text => {
                    try {
                        const data = JSON.parse(text);
                        if (data.status === 'success') {
                            alert('All records deleted successfully.');
                                fetchRecords(); // Refresh records after deletion
                                } else {
                                    alert('Delete failed: ' + data.message);
                                }
                            } catch (error) {
                                console.error('Error parsing JSON:', error);
                                console.error('Response text:', text); // Log the raw response
                            }
                        })
                            .catch(error => console.error('Error:', error));
            }
</script>
</body>
</html>