<?php
  $awxPlugin = new awxPlugin();
  $pluginConfig = $awxPlugin->config->get('Plugins','awx');
  if ($awxPlugin->auth->checkAccess($pluginConfig['ACL-READ'] ?? null) == false) {
    die();
  };

  $content = '
  <section class="section">
    <div class="row">
      <div class="col-lg-12">
        <div class="card">
          <div class="card-body">
            <center>
              <h4>Ansible AWX Dashboard</h4>
              <p>Monitor and manage your Ansible AWX jobs.</p>
            </center>
          </div>
        </div>
      </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row">
      <div class="col-xxl-3 col-md-6">
        <div class="card info-card">
          <div class="card-body">
            <h5 class="card-title">Total Jobs</h5>
            <div class="d-flex align-items-center">
              <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                <i class="bi bi-list-task"></i>
              </div>
              <div class="ps-3">
                <h6 id="totalJobs">0</h6>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-xxl-3 col-md-6">
        <div class="card info-card success-card">
          <div class="card-body">
            <h5 class="card-title">Successful Jobs</h5>
            <div class="d-flex align-items-center">
              <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                <i class="bi bi-check-circle"></i>
              </div>
              <div class="ps-3">
                <h6 id="successfulJobs">0</h6>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-xxl-3 col-md-6">
        <div class="card info-card warning-card">
          <div class="card-body">
            <h5 class="card-title">Running Jobs</h5>
            <div class="d-flex align-items-center">
              <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                <i class="bi bi-play-circle"></i>
              </div>
              <div class="ps-3">
                <h6 id="runningJobs">0</h6>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-xxl-3 col-md-6">
        <div class="card info-card danger-card">
          <div class="card-body">
            <h5 class="card-title">Failed Jobs</h5>
            <div class="d-flex align-items-center">
              <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                <i class="bi bi-x-circle"></i>
              </div>
              <div class="ps-3">
                <h6 id="failedJobs">0</h6>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Jobs Table -->
    <div class="row">
      <div class="col-lg-12">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title">Recent Jobs</h5>
            <div class="container">
              <div class="row justify-content-center">
                <table class="table table-striped" id="awxJobsTable"></table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Job Details Modal -->
  <div class="modal fade" id="jobDetailsModal" tabindex="-1" role="dialog" aria-labelledby="jobDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="jobDetailsModalLabel">Job Details</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
            <span aria-hidden="true"></span>
          </button>
        </div>
        <div class="modal-body">
          <div id="jobDetails"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Toast notification function
    function showToast(title, message, type = "error") {
      toast(title, "", message, type === "error" ? "danger" : type);
    }

    // Function to update statistics
    function updateStatistics(jobs) {
      const stats = {
        total: jobs.length,
        successful: jobs.filter(job => job.status === "successful").length,
        running: jobs.filter(job => job.status === "running").length,
        failed: jobs.filter(job => job.status === "failed").length
      };

      $("#totalJobs").text(stats.total);
      $("#successfulJobs").text(stats.successful);
      $("#runningJobs").text(stats.running);
      $("#failedJobs").text(stats.failed);
    }

    // Function to initialize the AWX jobs table
    function initializeAWXJobsTable() {
      queryAPI("GET", "/api/plugin/awx/ansible/jobs").done(function(data) {
        if (data.result === "Success") {
          updateStatistics(data.data);
          buildAWXJobsTable(data.data);
        } else {
          showToast("Error", "Failed to fetch AWX jobs: " + data.message);
        }
      });
    }

    // Function to build the AWX jobs table
    function buildAWXJobsTable(jobs) {
      $("#awxJobsTable").bootstrapTable({
        data: jobs,
        columns: [{
          field: "id",
          title: "ID",
          sortable: true
        }, {
          field: "name",
          title: "Name",
          sortable: true
        }, {
          field: "status",
          title: "Status",
          sortable: true,
          formatter: function(value) {
            let badgeClass = "bg-secondary";
            switch(value) {
              case "successful":
                badgeClass = "bg-success";
                break;
              case "failed":
                badgeClass = "bg-danger";
                break;
              case "running":
                badgeClass = "bg-primary";
                break;
              case "pending":
                badgeClass = "bg-warning";
                break;
            }
            return `<span class="badge ${badgeClass}">${value}</span>`;
          }
        }, {
          field: "started",
          title: "Started",
          sortable: true,
          formatter: function(value) {
            return value ? new Date(value).toLocaleString() : "";
          }
        }, {
          field: "finished",
          title: "Finished",
          sortable: true,
          formatter: function(value) {
            return value ? new Date(value).toLocaleString() : "Running";
          }
        }, {
          field: "operate",
          title: "Actions",
          align: "center",
          formatter: function(value, row) {
            return [
              `<button class="btn btn-primary btn-sm view-job" title="View Job Details" data-job-id="${row.id}">`,
              `<i class="bi bi-eye"></i>`,
              `</button>`
            ].join("");
          },
          events: {
            "click .view-job": function(e, value, row) {
              e.preventDefault();
              viewJobDetails(row.id);
            }
          }
        }]
      });

      // Add click handler for view job button
      $(document).on("click", ".view-job", function(e) {
        e.preventDefault();
        const jobId = $(this).data("job-id");
        viewJobDetails(jobId);
      });
    }

    // Function to format elapsed time
    function formatElapsedTime(seconds) {
      if (!seconds) return "";
      const minutes = Math.floor(seconds / 60);
      const remainingSeconds = (seconds % 60).toFixed(0);
      if (minutes > 0) {
        return `${minutes}m ${remainingSeconds}s`;
      }
      return `${remainingSeconds}s`;
    }

    // Function to format activity timestamp
    function formatActivityTime(timestamp) {
      if (!timestamp) return "";
      return new Date(timestamp).toLocaleString();
    }

    // Function to load job activity stream
    function loadJobActivityStream(jobId) {
      queryAPI("GET", "/api/plugin/awx/ansible/jobs/" + jobId + "/activity_stream").done(function(data) {
        if (data.result === "Success" && data.data && data.data.results) {
          let activityHtml = `
            <div class="mb-3">
              <h6>Activity Stream</h6>
              <div class="table-responsive">
                <table class="table table-sm">
                  <thead>
                    <tr>
                      <th>Time</th>
                      <th>Action</th>
                      <th>Description</th>
                    </tr>
                  </thead>
                  <tbody>
          `;

          data.data.results.forEach(activity => {
            activityHtml += `
              <tr>
                <td>${formatActivityTime(activity.timestamp)}</td>
                <td>${activity.operation || ""}</td>
                <td>${activity.changes || activity.description || ""}</td>
              </tr>
            `;
          });

          activityHtml += `
                  </tbody>
                </table>
              </div>
            </div>
          `;

          $("#jobActivityStream").html(activityHtml);
        } else {
          $("#jobActivityStream").html('<div class="alert alert-info">No activity stream available</div>');
        }
      }).fail(function(jqXHR, textStatus, errorThrown) {
        $("#jobActivityStream").html('<div class="alert alert-danger">Failed to load activity stream</div>');
        console.error("Activity Stream Error:", jqXHR.responseText);
      });
    }

    // Function to view job details
    function viewJobDetails(jobId) {
      queryAPI("GET", "/api/plugin/awx/ansible/jobs/" + jobId).done(function(data) {
        if (data.result === "Success") {
          const details = data.data;
          let detailsHtml = `
            <div class="mb-3">
              <h6>Job Information</h6>
              <table class="table">
                <tr><td><strong>ID:</strong></td><td>${details.id || ""}</td></tr>
                <tr><td><strong>Name:</strong></td><td>${details.name || ""}</td></tr>
                <tr><td><strong>Description:</strong></td><td>${details.description || ""}</td></tr>
                <tr><td><strong>Status:</strong></td><td><span class="badge ${getStatusBadgeClass(details.status)}">${details.status || ""}</span></td></tr>
                <tr><td><strong>Started:</strong></td><td>${details.started ? new Date(details.started).toLocaleString() : ""}</td></tr>
                <tr><td><strong>Finished:</strong></td><td>${details.finished ? new Date(details.finished).toLocaleString() : "Running"}</td></tr>
                <tr><td><strong>Elapsed:</strong></td><td>${formatElapsedTime(details.elapsed)}</td></tr>
                <tr><td><strong>Template:</strong></td><td>${details.summary_fields?.job_template?.name || ""}</td></tr>
                <tr><td><strong>Project:</strong></td><td>${details.summary_fields?.project?.name || ""}</td></tr>
                <tr><td><strong>Inventory:</strong></td><td>${details.summary_fields?.inventory?.name || ""}</td></tr>
                <tr><td><strong>Credentials:</strong></td><td>${details.summary_fields?.credentials?.map(c => c.name).join(", ") || ""}</td></tr>
                <tr><td><strong>Launch Type:</strong></td><td>${details.launch_type || ""}</td></tr>
                <tr><td><strong>Job Type:</strong></td><td>${details.job_type || ""}</td></tr>
              </table>
            </div>
          `;

          if (details.job_explanation) {
            detailsHtml += `
              <div class="mb-3">
                <h6>Job Explanation</h6>
                <div class="alert \${details.failed ? 'alert-danger' : 'alert-info'}">
                  ${details.job_explanation}
                </div>
              </div>`;
          }

          if (details.extra_vars) {
            try {
              const vars = typeof details.extra_vars === "string" ? JSON.parse(details.extra_vars) : details.extra_vars;
              detailsHtml += `
                <div class="mb-3">
                  <h6>Variables</h6>
                  <pre><code>${JSON.stringify(vars, null, 2)}</code></pre>
                </div>
              `;
            } catch (e) {
              console.error("Failed to parse extra_vars:", e);
            }
          }

          detailsHtml += '<div id="jobActivityStream"></div>';

          $("#jobDetails").html(detailsHtml);
          $("#jobDetailsModal").modal("show");
          
          // Load activity stream after showing modal
          loadJobActivityStream(details.id);
        } else {
          showToast("Error", "Failed to fetch job details: " + data.message);
        }
      }).fail(function(jqXHR, textStatus, errorThrown) {
        showToast("Error", "Failed to fetch job details: " + (errorThrown || textStatus));
        console.error("API Error:", jqXHR.responseText);
      });
    }

    // Helper function to get status badge class
    function getStatusBadgeClass(status) {
      switch(status) {
        case "successful": return "bg-success";
        case "failed": return "bg-danger";
        case "running": return "bg-primary";
        case "pending": return "bg-warning";
        default: return "bg-secondary";
      }
    }

    // Initialize the table when the page loads
    $(document).ready(function() {
      initializeAWXJobsTable();

      // Refresh the table every 30 seconds
      setInterval(function() {
        $("#awxJobsTable").bootstrapTable("refresh", {
          silent: true,
          url: "/api/plugin/awx/ansible/jobs",
          onLoadSuccess: function(data) {
            if (data.result === "Success") {
              updateStatistics(data.data);
            }
          }
        });
      }, 30000);
    });
  </script>

  <style>
    .info-card {
      border-radius: 4px;
      background: #fff;
      padding: 16px;
      position: relative;
      margin-bottom: 30px;
    }

    .info-card .card-icon {
      font-size: 32px;
      line-height: 0;
      width: 64px;
      height: 64px;
      flex-shrink: 0;
      flex-grow: 0;
    }

    .info-card .card-icon i {
      color: #fff;
      font-size: 28px;
    }

    .info-card h6 {
      font-size: 28px;
      color: #012970;
      font-weight: 700;
      margin: 0;
      padding: 0;
    }

    .card-title {
      padding: 20px 0 15px 0;
      font-size: 18px;
      font-weight: 500;
      color: #012970;
      font-family: "Poppins", sans-serif;
    }

    .info-card .card-icon {
      background: #e6e6e6;
    }

    .success-card .card-icon {
      background: #26b56d;
    }

    .warning-card .card-icon {
      background: #f5b82e;
    }

    .danger-card .card-icon {
      background: #ff4444;
    }
  </style>
';

return $content;