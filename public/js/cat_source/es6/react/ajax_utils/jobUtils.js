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
            xhrFields: { withCredentials: true },
            url : APP.getRandomUrl() + "api/v2/jobs/" + job.id +"/" + job.password + "/translator"
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
            delivery_date: Math.round(date),
            timezone: Number(timezone),
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
            url: APP.getRandomUrl() + "api/v2/jobs/" + job.id + "/" + job.password + "/translator"
        });
    },
    sendPERequest: function (service_url, date, timezone, job, project) {
        /*
        example job xliff data 
        {
            "job": {
                "id": "5",
                "password": "dcddde6e7320",
                "output_content": {
                    "5": {
                        "document_content": "<xliff>...</xliff>",
                        "output_filename": "Oviedo-part.docx"
                    },
                    "6": {
                        ...
                    },
                    ...
                }
            }
        } */
        var xliffDataObject = $.ajax({
            type: "get",
            xhrFields: { withCredentials: true },
            url : APP.getRandomUrl() + "api/v2/jobs/" + job.id + "/" + job.password + "/xliff",
            dataType: "json",
            success: function(json) {
                var xliffData = json["job"]["output_content"];
                var matecatData = {
                    service_url: service_url,
                    delivery_date: Math.round(date / 1000),
                    timezone: timezone
                };
                var serviceData = {
                    uid: APP.USER.STORE.user.uid,
                    id_team: project.id_team,
                    source_language: job.source,
                    target_language: job.target,
                    text_format: "xliff",
                    json: xliffData,
                    project_name: project.name
                };
                $.ajax({
                    async: true,
                    data: JSON.stringify(serviceData),
                    contentType: "application/json",
                    dataType: "json",
                    type: "POST",
                    url: service_url + "/pe/translate"
                });
                return $.ajax({
                    async: true,
                    data: matecatData,
                    type: "POST",
                    url: APP.getRandomUrl() + "api/v2/jobs/" + job.id + "/" + job.password + "/translator"
                });
            }
        });
        return xliffDataObject;
    }

};