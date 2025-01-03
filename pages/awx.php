<?php
  $awxPlugin = new awxPlugin();
  $pluginConfig = $awxPlugin->config->get('Plugins','awx');
  if ($cmdbPlugin->auth->checkAccess($pluginConfig['ACL-READ'] ?? null) == false) {
    die();
  };

  $content = '

  <section class="section">
    <div class="row">
      <div class="col-lg-12">
        <div class="card">
          <div class="card-body">
            <center>
              <h4>CMDB</h4>
              <p>A CMDB.</p>
            </center>
          </div>
        </div>
      </div>
    </div>
    <br>
    <div class="row">
      <div class="col-lg-12">
        <div class="card">
          <div class="card-body">
            <div class="container">
              <div class="row justify-content-center">

                <table class="table table-striped" id="cmdbTable"></table>

              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <br>
  </section>


  <!-- Record Modal -->
  <div class="modal fade" id="recordModal" tabindex="-1" role="dialog" aria-labelledby="recordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="recordModalLabel">CMDB Record</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
            <span aria-hidden="true"></span>
          </button>
        </div>
        <div class="modal-body" id="recordModelBody">
          <form id="recordForm">
          </form>
        </div>
        <div class="modal-footer">';
          if ($cmdbPlugin->auth->checkAccess($pluginConfig['ACL-JOB'] ?? null)) {
            if ($cmdbPlugin->config->get('Plugins','CMDB')['Ansible-JobByLabel']) {
              $content .= '
              <button type="button" class="btn btn-info dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">Run Job</button>
              <ul class="dropdown-menu">';
                foreach ($cmdbPlugin->config->get('Plugins','CMDB')['Ansible-Tag'] as $tag) {
                  $content .= '<li><a class="dropdown-item runJob" name="'.$tag.'">'.$tag.'</a></li>';
                }
                $content .= '</ul>';
            } else {
              $content .= '<button type="button" class="btn btn-info runJob">Run Job</button>';
            }
          } $content .= '
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button id="modalSubmit" type="button" class="btn btn-success" onclick="saveSomething();">Save</button>
        </div>
      </div>
    </div>
  </div>

  
  <!-- Manage Sections/Columns Modal -->
  <div class="modal fade" id="manageModal" tabindex="-1" role="dialog" aria-labelledby="manageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="manageModalLabel">Configure CMDB Sections & Columns</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
            <span aria-hidden="true"></span>
          </button>
        </div>
        <div class="modal-body" id="columnsModelBody">
          <table class="table table-striped" id="sectionsTable"></table>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Section Modal -->
  <div class="modal fade pt-5" id="sectionModal" tabindex="-1" role="dialog" aria-labelledby="sectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="sectionModalLabel">CMDB Section</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
            <span aria-hidden="true"></span>
          </button>
        </div>
        <div class="modal-body" id="sectionModalBody">
          <div class="form-group">
            <label for="sectionName">Section Name</label>
            <input type="text" class="form-control" id="sectionName">
            <small class="form-text text-muted" id="sectionNameHelp">The name for the CMDB Section</small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="button" class="btn btn-success" data-bs-dismiss="modal" id="sectionSubmit">Save</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Column Modal -->
  <div class="modal fade pt-5" id="columnModal" tabindex="-1" role="dialog" aria-labelledby="columnModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="columnModalLabel">CMDB Column</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
            <span aria-hidden="true"></span>
          </button>
        </div>
        <div class="modal-body" id="columnModalBody">
          <div class="form-group">
            <label for="columnName">Column Name</label>
            <input type="text" class="form-control" id="columnName">
            <small class="form-text text-muted" id="columnNameHelp">The name for the CMDB Column</small>
          </div>
          <div class="form-group">
            <label for="columnDescription">Column Description</label>
            <input type="text" class="form-control" id="columnDescription">
            <small class="form-text text-muted" id="columnDescriptionHelp">The description of the CMDB Column</small>
          </div>
          <div class="form-group">
            <label for="columnDataType">Data Type</label>
            <select class="form-select" id="columnDataType" aria-describedby="columnDataTypeHelp">
              <option value="TEXT">Text</option>
              <option value="INTEGER">Integer</option>
              <option value="BOOLEAN">Boolean</option>
            </select>
            <small class="form-text text-muted" id="columnDataTypeHelp">The CMDB Column Data Type</small>
          </div>
          <div class="form-group">
            <label for="columnFieldType">Field Type</label>
            <select class="form-select" id="columnFieldType" aria-describedby="columnFieldTypeHelp">
              <option value="INPUT">Input</option>
              <option value="SELECT" disabled>Select</option>
            </select>
            <small class="form-text text-muted" id="columnFieldTypeHelp">The CMDB Column Field Type</small>
          </div>
          <div class="form-group">
            <label for="columnSection">Section</label>
            <select class="form-select" id="columnSection" aria-describedby="columnSectionHelp">
            </select>
            <small class="form-text text-muted" id="columnSectionHelp">The CMDB Column Assigned Section</small>
          </div>
          <br>
          <div class="form-group">
            <div class="form-check form-switch">
              <label class="form-check-label" for="columnVisible">Visible</label>
              <input class="form-check-input" type="checkbox" id="columnVisible" name="columnVisible">
              <small class="form-text text-muted" id="columnVisibleHelp">Whether the field is visible in the table view by default</small>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="button" class="btn btn-success" data-bs-dismiss="modal" id="columnSubmit">Save</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Ansible Job Select Modal -->
  <div class="modal fade" id="ansibleJobSelectModal" tabindex="-1" role="dialog" aria-labelledby="ansibleJobSelectLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="columnModalLabel">Ansible - Job Select</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
            <span aria-hidden="true"></span>
          </button>
        </div>
        <div class="modal-body" id="ansibleJobSelectModalBody">
          <div class="overlay"></div>
            <p>Select an Ansible Job to run from the list below.</p>
            <div class="alert alert-primary" role="alert">
            <h4 class="alert-heading">Templates</h4>
            <select class="form-select" id="ansibleJobs"></select>
          </div>
        </div>
        <div class="modal-footer">
          <div id="jobOutput" role="alert" style="width:100%;"></div>';
          if ($cmdbPlugin->auth->checkAccess($pluginConfig['ACL-JOB'] ?? null)) { $content .= '
          <button type="button" class="btn btn-success" id="submitJob">Submit Job</button>';} $content .= '
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Function to check if database requires rebuild
    var rebuildRequired = false;
    queryAPI("GET","/api/plugin/cmdb/dbRebuild").done(function(data) {
      if (data["result"] == "Warning") {
        toast(data["result"],"",data["message"],"warning","30000");
        rebuildRequired = true;
      } else {
        rebuildRequired = false
      }
    });

    // Function to build the CMDB Sections Table
    function buildSectionsTable() {
      $("#sectionsTable").bootstrapTable({
        url: "/api/plugin/cmdb/sections",
        dataField: "data",
        pagination: true,
        search: true,
        showRefresh: true,
        exportTypes: ["json", "xml", "csv", "txt", "excel", "sql"],
        showColumns: true,
        buttonsOrder: "btnAddSection,btnAddColumn,refresh,columns,export,filterControlSwitch",
        reorderableRows: true,
        rowAttributes: "rowAttributes",
        onReorderRow: onReorderSectionsRow,
        detailView: true,
        detailFormatter: detailFormatter,
        onExpandRow: buildColumnsTable,
        buttons: "sectionButtons",
        columns: [{
          field: "id",
          title: "ID",
          visible: false
        },{
          field: "name",
          title: "Section Name",
        },{
          field: "weight",
          title: "Section Weight",
          visible: false
        },{
          title: "Actions",
          formatter: "actionFormatter",
          events: "sectionActionEvents"
        }]
      });
    }

    function rowAttributes(row, index) {
      return {
        "id": "row-"+row.id
      }
    }

    function onReorderColumnsRow(data,row,oldrow,table) {
      var key = data.findIndex(item => item.id === row.id) + 1;
      queryAPI("PATCH","/api/plugin/cmdb/column/"+row.id+"/weight",{"weight": key}).done(function(data) {
        if (data["result"] == "Success") {
            toast(data["result"],"",data["message"],"success");
        } else if (data["result"] == "Error") {
            toast(data["result"],"",data["message"],"danger");
        } else {
            toast("API Error","","Failed to edit column position","danger","30000");
        }
      }).fail(function() {
        toast("API Error","","Failed to edit column position","danger","30000");
      });
    }

    function onReorderSectionsRow(data,row,oldrow,table) {
      var key = data.findIndex(item => item.id === row.id) + 1;
      queryAPI("PATCH","/api/plugin/cmdb/section/"+row.id+"/weight",{"weight": key}).done(function(data) {
        if (data["result"] == "Success") {
            toast(data["result"],"",data["message"],"success");
        } else if (data["result"] == "Error") {
            toast(data["result"],"",data["message"],"danger");
        } else {
            toast("API Error","","Failed to edit section position","danger","30000");
        }
      }).fail(function() {
        toast("API Error","","Failed to edit section position","danger","30000");
      });
    }

    // Function to build the CMDB Columns Table
    function buildColumnsTable(index, row, detail) {
      if (!row) return;
      const columnsTableId = `#columns-table-${index}`;
      $(columnsTableId).bootstrapTable({
        url: "/api/plugin/cmdb/columns?section="+row.id,
        dataField: "data",
        reorderableRows: true,
        rowAttributes: "rowAttributes",
        onReorderRow: onReorderColumnsRow,
        columns: [{
          field: "id",
          title: "ID",
          visible: false
        },{
          field: "name",
          title: "Column Name",
          filterControl: "select"
        },{
          field: "description",
          title: "Description",
          filterControl: "select"
        },{
          field: "dataType",
          title: "Data Type",
          filterControl: "select"
        },{
          field: "fieldType",
          title: "Field Type",
          filterControl: "select"
        },{
          field: "weight",
          title: "Weight",
          filterControl: "select",
          visible: false
        },{
          title: "Actions",
          formatter: "actionFormatter",
          events: "columnActionEvents"
        }]
      });
    }

    function createTableHtml(index, items) {
      let html = [];
      let theme = getCookie("theme") == "dark" ? " table-dark" : "";
      
      html.push(`<table class="table table-striped`+theme+`" id="columns-table-` + index +`"></table>`);
      return html.join("");
    }

    function detailFormatter(index, row) {
      let html = [];
      if (row) {
        html.push(createTableHtml(index, Array.isArray(row) ? row.Items : Object.values(row)));
      }
      return html.join("");
    }

    function buildCMDBTable(jsonData) {
      const columns = jsonData.map(column => ({
          field: column.columnName,
          title: column.name,
          sortable: true,
          visible: column.visible === 1,
          filterControl: column.fieldType.toLowerCase()
      }));

      // Append the actions column
      columns.push({
          field: "actions",
          title: "Actions",
          formatter: "actionFormatter",
          events: "cmdbActionEvents"
      });

      $("#cmdbTable").bootstrapTable({
          url: "/api/plugin/cmdb/records",
          dataField: "data",
          sortable: true,
          pagination: true,
          search: true,
          showExport: true,
          showRefresh: true,
          exportTypes: ["json", "xml", "csv", "txt", "excel", "sql"],
          showColumns: true,
          buttons: "cmdbButtons",
          buttonsOrder: "btnAddRecord,refresh,columns,export,filterControlSwitch,btnEditColumns",
          filterControl: true,
          filterControlVisible: false,
          showFilterControlSwitch: true,
          columns: columns
      });
    }

    function createForm(jsonData) {
      const formDiv = document.getElementById("recordForm"); // Make sure there\'s a div with id "formDiv" in your HTML
      const sections = {};

      // Group items by section_name
      jsonData.forEach(item => {
          if (!sections[item.section_name]) {
              sections[item.section_name] = [];
          }
          sections[item.section_name].push(item);
      });

      // Sort sections by section_weight
      const sortedSections = Object.keys(sections).sort((a, b) => {
          const sectionA = sections[a][0].section_weight;
          const sectionB = sections[b][0].section_weight;
          return sectionA - sectionB;
      });

      // Create form sections and items
      sortedSections.forEach(sectionName => {
          const sectionItems = sections[sectionName];

          // Create section header
          const sectionHeader = document.createElement("h3");
          sectionHeader.innerText = sectionName;
          formDiv.appendChild(sectionHeader);

          // Sort items by column_weight
          sectionItems.sort((a, b) => a.column_weight - b.column_weight);

          const id = document.createElement("input");
          id.id = "recordId";
          id.hidden = true;
          formDiv.appendChild(id);

          // Create form items for each section
          sectionItems.forEach(item => {
              const formGroup = document.createElement("div");
              formGroup.className = "form-group";

              const label = document.createElement("label");
              label.htmlFor = `record${item.columnName}`;
              label.innerText = item.name;

              const input = document.createElement("input");
              input.type = item.dataType === "INTEGER" ? "number" : "text";
              input.className = "form-control info-field";
              input.id = `record${item.columnName}`;
              input.name = item.columnName;

              const small = document.createElement("small");
              small.innerText = item.description;
              small.className = "form-text text-muted";
              small.id = `record${item.columnName}Help`;

              formGroup.appendChild(label);
              formGroup.appendChild(input);
              formGroup.appendChild(small);
              formDiv.appendChild(formGroup);
          });

          formDiv.appendChild(document.createElement("br"));
      });

      // Detect Changes
      $(".info-field").change(function(elem) {
        toast("Configuration","",$(elem.target.previousElementSibling).text()+" has changed.<br><small>Save configuration to apply changes.</small>","warning");
        $(this).addClass("changed");
      });
    }

    // Function to update elements
    function updateInputs(row) {
      $("#recordModal input").val("").removeClass("changed");
      for (const key in row) {
        if (row.hasOwnProperty(key)) {
          $(`#record${key}`).val(row[key]);
        }
      }
      $("#recordId").val(row.id);
    }

    // Build Form & Table Layout
    queryAPI("GET", "/api/plugin/cmdb/layout").done(function(data) {
      buildCMDBTable(data.data);
      createForm(data.data);
    });

    // CMDB Row Actions Buttons
    function actionFormatter(value, row, index) {
      return [
        ';
        if ($cmdbPlugin->auth->checkAccess($pluginConfig['ACL-WRITE'] ?? null)) { $content .= '
          `<a class="edit" title="Edit">`,
          `<i class="fa fa-pencil"></i>`,
          `</a>&nbsp;`,
          `<a class="delete" title="Delete">`,
          `<i class="fa fa-trash"></i>`,
          "</a>"
        ';}
        $content .= '
      ].join("")
    }

    // CMDB Row Action Events
    window.cmdbActionEvents = {
      "click .edit": function (e, value, row, index) {
        updateInputs(row);
        $("#recordModal").addClass("editModal").removeClass("newModal").modal("show");
        $(".runJob").attr("hidden",false);
        // Update Submit Button To Edit Record
        $("#modalSubmit").attr("onclick","editRecordSubmit();");
      },
      "click .delete": function (e, value, row, index) {
        if(confirm("Are you sure you want to delete "+row.id+" from the CMDB? This is irriversible.") == true) {
          queryAPI("DELETE","/api/plugin/cmdb/record/"+row.id).done(function(data) {
            if (data["result"] == "Success") {
              toast(data["result"],"",data["message"],"success");
              $("#cmdbTable").bootstrapTable("refresh");
            } else if (data["result"] == "Error") {
              toast(data["result"],"",data["message"],"danger","30000");
            } else {
              toast("Error","","Failed to remove record: "+row.id,"danger","30000");
            }
          }).fail(function() {
            toast("API Error","","Failed to remove record: "+row.id,"danger","30000");
          });
        }
      }
    }

    // CMDB Table Buttons
    function cmdbButtons() {
      return {
        ';
        // Check if user has Write Permission and display add button
        if ($cmdbPlugin->auth->checkAccess($pluginConfig['ACL-WRITE'] ?? null)) { $content .= '
        btnAddRecord: {
          text: "Add Record",
          icon: "bi-plus-lg",
          event: function() {
            // Clear all values from new record modal
            $("#recordModal input").val("").removeClass("changed");
            $(".runJob").attr("hidden",true);
            // Update Submit Button To New Record
            $("#modalSubmit").attr("onclick","newRecordSubmit();");
            // Show new record modal
            $("#recordModal").modal("show");
          },
          attributes: {
            title: "Add a CMDB record",
            style: "background-color:#4bbe40;border-color:#4bbe40;"
          }
        },';}
        if ($cmdbPlugin->auth->checkAccess($pluginConfig['ACL-ADMIN'] ?: 'ACL-ADMIN')) {
          $content .= '
          btnEditColumns: {
            text: "Edit Columns",
            icon: "bi-layout-text-window",
            event: function() {
              $("#manageModal").modal("show");
              buildSectionsTable();
            },
            attributes: {
              title: "Edit The CMDB Columns",
              style: "background-color:#9e3ee3;border-color:#9e3ee3;"
            }
          },';
          if ($cmdbPlugin->rebuildRequired()) {
            $content .= '
            btnRebuildDB: {
              text: "Rebuild Database",
              icon: "bi-arrow-clockwise",
              event: function() {
                rebuildDB();
              },
              attributes: {
                title: "Rebuild The CMDB Database to apply changes",
                style: "background-color:#ffb01c;border-color:#ffb01c;"
              }
            }';
          }
        }
        $content .= '
      }
    }

    // CMDB Section Row Action Events
    window.sectionActionEvents = {
      "click .edit": function (e, value, row, index) {
        // Clear all values from section modal
        $("#sectionModal input").val("").removeClass("changed");
        // Populate Section Name
        $("#sectionName").val(row.name);
        // Populate Modal Title
        $("#sectionModalLabel").text("Edit Section: "+row.name);
        // Show Section Modal
        $("#sectionModal").modal("show");
        // Update Submit Button To Edit Section
        $("#sectionSubmit").attr("onclick","editSectionSubmit("+row.id+");");
      },
      "click .delete": function (e, value, row, index) {
        if(confirm("Are you sure you want to delete Section: "+row.name+" from the CMDB? This is irriversible.") == true) {
          queryAPI("DELETE","/api/plugin/cmdb/section/"+row.id).done(function(data) {
            if (data["result"] == "Success") {
              toast(data["result"],"",data["message"],"success");
              $("#sectionsTable").bootstrapTable("refresh");
            } else if (data["result"] == "Error") {
              toast(data["result"],"",data["message"],"danger","30000");
            } else {
              toast("Error","","Failed to remove section: "+row.name,"danger","30000");
            }
          }).fail(function() {
            toast("API Error","","Failed to remove section: "+row.name,"danger","30000");
          });
        }
      }
    }

    // CMDB Section Buttons
    function sectionButtons() {
      return {
        ';
        // Check if user has Admin Permission and display add button
        if ($cmdbPlugin->auth->checkAccess($pluginConfig['ACL-ADMIN'] ?: 'ACL-ADMIN')) { $content .= '
        btnAddSection: {
          text: "Add Section",
          icon: "bi-plus-lg",
          event: function() {
            // Clear all values from section modal
            $("#sectionModal input").val("").removeClass("changed");
            // Populate Modal Title
            $("#sectionModalLabel").text("New Section");
            // Update Submit Button To New Section
            $("#sectionSubmit").attr("onclick","newSectionSubmit();");
            // Show section modal
            $("#sectionModal").modal("show");
          },
          attributes: {
            title: "Add a CMDB Section",
            style: "background-color:#4bbe40;border-color:#4bbe40;"
          }
        },
        btnAddColumn: {
          text: "Add Column",
          icon: "bi-plus-lg",
          event: function() {
            // Clear all values from columns modal
            $("#columnModal input").val("").removeClass("changed");
            // Populate Modal Title
            $("#columnModalLabel").text("New Column");
            // Update Submit Button To New Column
            $("#columnSubmit").attr("onclick","newColumnSubmit();");
            // Populate Column Dropdown
            updateSectionDropdown();
            // Show columns modal
            $("#columnModal").modal("show");
          },
          attributes: {
            title: "Add a CMDB Column",
            style: "background-color:#d0a624;border-color:#d0a624;"
          }
        }';}
        $content .= '
      }
    }

    // CMDB Column Row Action Events
    window.columnActionEvents = {
      "click .edit": function (e, value, row, index) {
        // Clear all values from column modal
        $("#columnModal input").val("").removeClass("changed");
        // Populate Modal Title
        $("#sectionModalLabel").text("Edit Column: "+row.name);
        // Populate Column Fields
        $("#columnName").val(row.name);
        $("#columnDescription").val(row.description);
        $("#columnDataType").val(row.dataType);
        $("#columnFieldType").val(row.fieldType);
        $("#columnVisible").prop("checked", row.visible);
        // Populate Column Dropdown
        updateSectionDropdown(row);
        // Show Column Modal
        $("#columnModal").modal("show");
        // Update Submit Button To Edit Column
        var tableId = $($(e.target).parents().eq(4)[0]).attr("id");
        $("#columnSubmit").attr("onclick",`editColumnSubmit("`+row.id+`","`+tableId+`");`);
      },
      "click .delete": function (e, value, row, index) {
        if(confirm("Are you sure you want to delete Column: "+row.name+" from the CMDB? This is irriversible.") == true) {
          queryAPI("DELETE","/api/plugin/cmdb/column/"+row.id).done(function(data) {
            if (data["result"] == "Success") {
              toast(data["result"],"",data["message"],"success");
              $($(e.target).parents().eq(4)[0]).bootstrapTable("refresh");
            } else if (data["result"] == "Error") {
              toast(data["result"],"",data["message"],"danger","30000");
            } else {
              toast("Error","","Failed to remove column: "+row.name,"danger","30000");
            }
          }).fail(function() {
            toast("API Error","","Failed to remove column: "+row.name,"danger","30000");
          });
        }
      }
    }

    function newRecordSubmit() {
      var formData = $("#recordForm .changed").serializeArray();
      
      // Include unchecked checkboxes in the formData
      $("#recordForm input.changed[type=checkbox]").each(function() {
          formData.push({ name: this.name, value: this.checked ? true : false });
      });

      var configData = {};
      formData.forEach(function(item) { 
          var keys = item.name.split("[").map(function(key) {
              return key.replace("]","");
          });
          var temp = configData;
          keys.forEach(function(key, index) {
              if (index === keys.length - 1) {
                  temp[key] = item.value;
              } else {
                  temp[key] = temp[key] || {};
                  temp = temp[key];
              }
          });
      });
      queryAPI("POST","/api/plugin/cmdb/record",configData).done(function(data) {
        if (data["result"] == "Success") {
            toast(data["result"],"",data["message"],"success");
            $("#cmdbTable").bootstrapTable("refresh");
            $("#recordModal").modal("hide");
        } else if (data["result"] == "Error") {
            toast(data["result"],"",data["message"],"danger");
        } else {
            toast("API Error","","Failed to add new CMDB record","danger","30000");
        }
      });
    }

    function editRecordSubmit() {
      var id = $("#recordId").val();
      console.log(id);
      var formData = $("#recordForm .changed").serializeArray();
      
      // Include unchecked checkboxes in the formData
      $("#recordForm input.changed[type=checkbox]").each(function() {
          formData.push({ name: this.name, value: this.checked ? true : false });
      });

      var configData = {};
      formData.forEach(function(item) { 
          var keys = item.name.split("[").map(function(key) {
              return key.replace("]","");
          });
          var temp = configData;
          keys.forEach(function(key, index) {
              if (index === keys.length - 1) {
                  temp[key] = item.value;
              } else {
                  temp[key] = temp[key] || {};
                  temp = temp[key];
              }
          });
      });
      queryAPI("PATCH","/api/plugin/cmdb/record/"+id,configData).done(function(data) {
        if (data["result"] == "Success") {
            toast(data["result"],"",data["message"],"success");
            $("#cmdbTable").bootstrapTable("refresh");
            $("#recordModal").modal("hide");
        } else if (data["result"] == "Error") {
            toast(data["result"],"",data["message"],"danger");
        } else {
            toast("API Error","","Failed to edit CMDB record","danger","30000");
        }
      }).fail(function() {
        toast("API Error","","Failed to edit CMDB record","danger","30000");
      });
    }

    function newSectionSubmit() {
      var name = $("#sectionName").val() ?? null;
      if (name) {
        queryAPI("POST","/api/plugin/cmdb/sections",{"name": name}).done(function(data) {
          if (data["result"] == "Success") {
              toast(data["result"],"",data["message"],"success");
              $("#sectionsTable").bootstrapTable("refresh");
              $("#sectionModal").modal("hide");
          } else if (data["result"] == "Error") {
              toast(data["result"],"",data["message"],"danger");
          } else {
              toast("API Error","","Failed to edit CMDB record","danger","30000");
          }
        }).fail(function() {
          toast("API Error","","Failed to edit CMDB record","danger","30000");
        });
      } else {
        toast("Error","","The Section Name is required","danger","30000");
      }
    }

    function editSectionSubmit(id) {
      var name = $("#sectionName").val() ?? null;
      if (name) {
        queryAPI("PATCH","/api/plugin/cmdb/section/"+id,{"name": name}).done(function(data) {
          if (data["result"] == "Success") {
              toast(data["result"],"",data["message"],"success");
              $("#sectionsTable").bootstrapTable("refresh");
              $("#sectionModal").modal("hide");
          } else if (data["result"] == "Error") {
              toast(data["result"],"",data["message"],"danger");
          } else {
              toast("API Error","","Failed to edit section","danger","30000");
          }
        }).fail(function() {
          toast("API Error","","Failed to edit section","danger","30000");
        });
      } else {
        toast("Error","","The Section Name is required","danger","30000");
      }
    }

    function newColumnSubmit() {
      var postArr = {
          "name": $("#columnName").val() ?? null,
          "description": $("#columnDescription").val() ?? null,
          "dataType": $("#columnDataType").val() ?? null,
          "fieldType": $("#columnFieldType").val() ?? null,
          "section": $("#columnSection").val() ?? null,
          "visible": $("#columnVisible").val() ?? null,
      };
      if (postArr) {
        queryAPI("POST","/api/plugin/cmdb/columns",postArr).done(function(data) {
          if (data["result"] == "Success") {
              toast(data["result"],"",data["message"],"success");
              $("#sectionsTable").bootstrapTable("refresh");
              $("#columnModal").modal("hide");
          } else if (data["result"] == "Error") {
              toast(data["result"],"",data["message"],"danger");
          } else {
              toast("API Error","","Failed to add column","danger","30000");
          }
        }).fail(function() {
          toast("API Error","","Failed to add column","danger","30000");
        });
      } else {
        toast("Error","","Missing required fields","danger","30000");
      }
    }

    function editColumnSubmit(id,tableId) {
      var postArr = {
          "name": $("#columnName").val() ?? null,
          "description": $("#columnDescription").val() ?? null,
          "dataType": $("#columnDataType").val() ?? null,
          "fieldType": $("#columnFieldType").val() ?? null,
          "section": $("#columnSection").val() ?? null,
          "visible": $("#columnVisible").is(":checked"),
      };
      if (postArr) {
        queryAPI("PATCH","/api/plugin/cmdb/column/"+id,postArr).done(function(data) {
          if (data["result"] == "Success") {
              toast(data["result"],"",data["message"],"success");
              $("#"+tableId).bootstrapTable("refresh");
              $("#columnModal").modal("hide");
          } else if (data["result"] == "Error") {
              toast(data["result"],"",data["message"],"danger");
          } else {
              toast("API Error","","Failed to edit column","danger","30000");
          }
        }).fail(function() {
          toast("API Error","","Failed to edit column","danger","30000");
        });
      } else {
        toast("Error","","Missing required fields","danger","30000");
      }
    }

    function updateSectionDropdown(row = {}) {
      queryAPI("GET","/api/plugin/cmdb/sections").done(function(data) {
        const sectionDropdown = $("#columnSection");
        sectionDropdown.html("");
        $.each(data.data, function(index, item) {
            const option = $("<option></option>").val(item.id).text(item.name);
            sectionDropdown.append(option);
        });
        row.section ? sectionDropdown.val(row.section) : sectionDropdown.val("");
      });
    }

    function rebuildDB() {
      if(confirm("Are you sure you want to initiate a database rebuild? This will purge data from removed columns and is irreversible.") == true) {
        queryAPI("POST","/api/plugin/cmdb/dbRebuild/initiate").done(function(data) {
          if (data["result"] == "Success") {
              toast(data["result"],"",data["message"],"success");
              $(`*[name="btnRebuildDB"]`).attr("hidden",true);
          } else if (data["result"] == "Error") {
              toast(data["result"],"",data["message"],"danger");
          } else {
              toast("API Error","","Failed to initiate database rebuild","danger","30000");
          }
        }).fail(function() {
          toast("API Error","","Failed to initiate database rebuild","danger","30000");
        });
      }
    }

    $("#recordModal").on("click", ".runJob", function(elem) {
      if ($(this).attr("name") !== undefined) {
          var url = "/api/plugin/cmdb/ansible/templates?label="+$(this).attr("name");
      } else {
          var url = "/api/plugin/cmdb/ansible/templates"
      }
      $("#ansibleJobs").empty();
      $("#jobOutput").empty().removeClass("alert");
      queryAPI("GET",url).done(function(data) {
        if (data["result"] == "Success") {
          $.each(data.data, function() {
            $("#ansibleJobs").append("<option value="+this.id+">"+this.name+"</option>");
          });
          $("#ansibleJobSelectModal").modal("show");
        } else if (data["result"] == "Error") {
            toast(data["result"],"",data["message"],"danger");
        } else {
            toast("API Error","","Failed to query list of Ansible Job Templates","danger","30000");
        }
      }).fail(function() {
          toast("API Error","","Failed to query list of Ansible Job Templates","danger","30000");
      });
    });

    $("#submitJob").on("click", function(event) {
      $("#jobOutput").empty().removeClass("alert alert-success alert-danger");
      var jobId = $("#ansibleJobs").find(":selected").val();
      $("#ansibleJobs").on("change", function(elem) {
        jobId = $("#ansibleJobs").find(":selected").val();
      });

      var formData = $("#recordForm").serializeArray();
      
      // Include unchecked checkboxes in the formData
      $("#recordForm input[type=checkbox]").each(function() {
          formData.push({ name: this.name, value: this.checked ? true : false });
      });

      var configData = {};
      formData.forEach(function(item) { 
          var keys = item.name.split("[").map(function(key) {
              return key.replace("]","");
          });
          var temp = configData;
          keys.forEach(function(key, index) {
              if (index === keys.length - 1) {
                  temp[key] = item.value;
              } else {
                  temp[key] = temp[key] || {};
                  temp = temp[key];
              }
          });
      });
      
      queryAPI("POST","/api/plugin/cmdb/ansible/job/"+jobId,configData).done(function(data) {
        if (data["result"] == "Success") {
          var ansibleJob = data.data;
          if (ansibleJob.job) {
            $("#jobOutput").append(`<p>Job "+ansibleJob.id+" started successfully. Click <a href="'; $content .= $cmdbPlugin->config->get("Plugins","CMDB")["Ansible-URL"]; $content .= '/#/jobs/playbook/"+ansibleJob.id+"/output" target="_blank">here</a> to view the Job in Ansible</p>`);
            $("#jobOutput").addClass("alert alert-success");
          } else {
            $("#jobOutput").append("<p>Job failed to start. See below for more information.</p>");
            $("#jobOutput").append("<pre>"+JSON.stringify(ansibleJob,null,2)+"</pre>");
            $("#jobOutput").addClass("alert alert-danger");
          }
        } else if (data["result"] == "Error") {
            toast(data["result"],"",data["message"],"danger");
        } else {
            toast("API Error","","Failed to submit Ansible Job","danger","30000");
        }
      }).fail(function() {
          toast("API Error","","Failed to submit Ansible Job","danger","30000");
      });
    });
  </script>
';
return $content;