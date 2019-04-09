if (!API) {
    var API = {}
}


API.JOB = {
    /**
     * Change the password for the job
     * @param job
     * @param undo
     * @param old_pass
     */
    changeJobPassword: function(job, undo, old_pass) {
        var id = job.id;
        var password = job.password;

        return APP.doRequest({
            data: {
                action:		    "changePassword",
                res: 		    'obj',
                id: 		    id,
                password: 	    password,
                old_password: 	old_pass,
                undo:           undo
            },
            success: function(d){}
        });
    },
    checkSplitRequest: function (job, project, numsplit, arrayValues) {
        return APP.doRequest({
            data: {
                action: "splitJob",
                exec: "check",
                project_id: project.id,
                project_pass: project.password,
                job_id: job.id,
                job_pass: job.password,
                num_split: numsplit,
                split_values: arrayValues
            },
            success: function(d) {}
        });
    },
    confirmSplitRequest: function(job, project, numsplit, arrayValues) {

        return APP.doRequest({
            data: {
                action: "splitJob",
                exec: "apply",
                project_id: project.id,
                project_pass: project.password,
                job_id: job.id,
                job_pass: job.password,
                num_split: numsplit,
                split_values: arrayValues
            }
        });
    },
    confirmMerge: function(project, job) {

        return APP.doRequest({
            data: {
                action: "splitJob",
                exec: "merge",
                project_id: project.id,
                project_pass: project.password,
                job_id: job.id
            }

        });
    },
    sendTranslatorRequest: function (email, date, timezone, job) {
        var data = {
            email: email,
            delivery_date: Math.round(date/1000),
            timezone: timezone
        };
        return $.ajax({
            async: true,
            data: data,
            type: "POST",
            url : "/api/v2/jobs/" + job.id +"/" + job.password + "/translator"
        });
    },
    sendServiceRequest: function (service_url, date, timezone, job, projectName) {
        var matecatData = {
            service_url: service_url,
            delivery_date: Math.round(date / 1000),
            timezone: timezone
        };
        var serviceData = {
            id: job.id,
            password: job.password,
            delivery_date: Math.round(date / 1000),
            timezone: timezone,
            project_name: projectName,
            source: job.source,
            target: job.target
        };
        $.ajax({
            async: true,
            data: JSON.stringify(serviceData),
            contentType: "application/json",
            dataType: "json",
            type: "POST",
            url: service_url + "/job"
        });
        return $.ajax({
            async: true,
            data: matecatData,
            type: "POST",
            url: "/api/v2/jobs/" + job.id + "/" + job.password + "/translator"
        });
    }

};