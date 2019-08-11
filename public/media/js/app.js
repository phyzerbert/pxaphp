$(document).ready(function() {

  	/** Initialize tooltips */
  	$('[data-toggle="tooltip"]').tooltip();

  	/** Initialize popovers */
  	$('[data-toggle="popover"]').popover({
    	html: true
  	});

  	/** Initialize lightbox */
  	$('.image-link').magnificPopup({
  		type:'image',
	  	gallery:{
	    	enabled:true
	  	}
  	});

  	/** Load last 5 notifications */
  	$('#load_notifications_dropdown').on('show.bs.dropdown', function () {
  		
		$(".notifications_dropdown_content").html('');

		$.getJSON('/notifications/load', {}, function(data) {

			$(".loading-notifications").addClass("d-none");

			if(data) {

				$(".notifications_dropdown_content").html(data);
			}
		});

	});

});

/** Function which show and hide in password input */
function tooglePassword() {

	var type = $("#password").attr('type');
	if (type == 'text') {
        $("#password").prop('type', 'password');
    } else {
        $("#password").prop('type', 'text');
    }
}

/** Function which set notifications as viewed */
function notifications_readed() {

	$.getJSON('/notifications/readall', {}, function(data) {

		$(".set_read_notifications").addClass("d-none");

		$(".set_read_notifications").addClass('d-none')
		$(".new-notifications").addClass('d-none');
		$(".unviewed_notifications").html('');
	});
}

/** Check for new notifications */
function check_notifications() {

	$.getJSON('/notifications/check', {}, function(data) {

		if(data > 0) {

			$(".set_read_notifications").removeClass('d-none')
			$(".new-notifications").removeClass('d-none');
			$(".unviewed_notifications").html(data);
		}
	});
}

/** Follow topic function */
function follow_topic(topic_id) {

	$("#follow_"+topic_id).addClass('btn-secondary').addClass('btn-loading');

	$.getJSON('/topics/follow', { 'id': topic_id }, function(data) {

		$("#follow_"+topic_id).removeClass('btn-secondary').removeClass('btn-loading');

		if(data === true) {

			$("#follow_"+topic_id).addClass("d-none");
			$("#unfollow_"+topic_id).removeClass("d-none");

			var followers = $("#topic_"+topic_id+"_followers").html();
			if(followers < 1000) {
				followers++;
				$("#topic_"+topic_id+"_followers").html(followers);
			}
		}
	});
}

/** Unfollow topic function */
function unfollow_topic(topic_id) {

	$("#unfollow_"+topic_id).addClass('btn-loading');

	$.getJSON('/topics/unfollow', { 'id': topic_id }, function(data) {

		$("#unfollow_"+topic_id).removeClass('btn-loading');

		if(data === true) {

			$("#unfollow_"+topic_id).addClass("d-none");
			$("#follow_"+topic_id).removeClass("d-none");

			var followers = $("#topic_"+topic_id+"_followers").html();
			if(followers < 1000) {
				followers--;
				$("#topic_"+topic_id+"_followers").html(followers);
			}
		}
	});
}

/** Delete question function */
function delete_question(id) {

	if(id > 0) {

		$("#deleteQuestionId").val(id);
		$('#deleteQuestionModal').modal('show');
	}
}

/** Hide Question function */
function hide_question(id) {

	if(id > 0) {

		$("#hideQuestionId").val(id);
		$('#hideQuestionModal').modal('show');
	}
}

/** Close Question function */
function close_question(id) {

	if(id > 0) {

		$("#closeQuestionId").val(id);
		$('#closeQuestionModal').modal('show');
	}
}

/** Confirm close question modal */
function confirm_close_question() {

	$('#closeQuestionModal').modal('hide');

	var question_id = $("#closeQuestionId").val();

	$.getJSON('/questions/close', { 'id': question_id }, function(data) {

		if(data === true) {

  		$("#question_"+question_id+" .card-status").addClass('bg-gray-dark');
  		$("#answer_btn_"+question_id).removeClass('btn-outline-primary').addClass('btn-outline-secondary');
  		$("#answer_btn_"+question_id).html('<i class="fe fe-check"></i> Closed Question');

  		$("#question_"+question_id+" .can_close").hide();
  		$("#question_"+question_id+" .tag-closed").removeClass('d-none');
		}
	});
}

/** Follow Question function */
function follow_question(question_id) {

	$("#follow_"+question_id).addClass('btn-secondary').addClass('btn-loading');

	$.getJSON('/questions/follow', { 'id': question_id }, function(data) {

		$("#follow_"+question_id).removeClass('btn-secondary').removeClass('btn-loading');

		if(data === true) {

		  $("#follow_"+question_id).addClass("d-none");
  		$("#unfollow_"+question_id).removeClass("d-none");
  		$("#question_"+question_id+" .card-status").addClass('bg-yellow-lighter');
 			$("#question_"+question_id+" .tag-followed").removeClass('d-none');
		}
	});
}

/** Unfollow Question function */
function unfollow_question(question_id) {

	$("#unfollow_"+question_id).addClass('btn-loading');

	$.getJSON('/questions/unfollow', { 'id': question_id }, function(data) {

		$("#unfollow_"+question_id).removeClass('btn-loading');

		if(data === true) {

  		$("#unfollow_"+question_id).addClass("d-none");
  		$("#follow_"+question_id).removeClass("d-none");
  		$("#question_"+question_id+" .card-status").removeClass('bg-yellow-lighter');

  		$("#question_"+question_id+" .tag-followed").addClass('d-none');

  		$(".questions_followed #question_"+question_id).hide();
		}
	});
}

/** Edit question function */
function edit_question(id) {

	if(id > 0) {

		$("#edit_question_id").val(id);
		$('#editQuestionModal').modal('show');

		$.getJSON('/questions/get', { 'id': id }, function(data) {

			$("#editQuestionForm #q_title").val(data.title);
			$("#editQuestionForm #q_title_counter").html(data.title.length);
			
			$("#editQuestionForm #q_description").val(data.description);
			$("#editQuestionForm #q_description_counter").html(data.description.length);

			var selectElement = $('#editQuestionForm .selectize').eq(0);
			var selectize = selectElement.data('selectize');

			var topics = [];
			$.each(data.topics, function(i,e) {
        topics.push(e.id);
      });

      if (!!selectize) selectize.setValue(topics);

		});
	}
}

/** Sort stream function */
function sort_questions_by(url, type) {

  window.location.replace("/"+url+"?sort_by="+type+"&sort_type=questions");
}

/** Sort answers function */
function sort_answers_by(url, type) {

  window.location.replace("?sort_by="+type+"&sort_type=answers&url="+url);
}

/** Sort stream function */
function sort_stream_by(url, type) {

  window.location.replace("?sort_by="+type+"&url="+url);
}

/** Vote Question function */
function vote_question(question_id) {

	var value = parseInt($("#question_"+question_id+" .votes-number").val().replace(' votes', ''));

	$.getJSON('/questions/vote', { 'id': question_id }, function(data) {

		if(data > 0) {

			var new_value = value + data;
  		$("#question_"+question_id+" .votes-number").val(new_value+' votes');
  		$("#question_"+question_id+" .click-unvote").removeClass('active');
  		$("#question_"+question_id+" .click-vote").addClass('active');
		}
	});
}

/** Unvote Question function */
function unvote_question(question_id) {

	var value = parseInt($("#question_"+question_id+" .votes-number").val().replace(' votes', ''));

	$.getJSON('/questions/unvote', { 'id': question_id }, function(data) {

		if(data > 0) {

			var new_value = value - data;
  		$("#question_"+question_id+" .votes-number").val(new_value+' votes');
  		$("#question_"+question_id+" .click-vote").removeClass('active');
  		$("#question_"+question_id+" .click-unvote").addClass('active');
		}
	});
}

/** Function which get image's information and display it */
function getImage(input, append_div, max_images) {

  	var maxImages = parseInt(max_images);
  	var addedImages = 0;

  	var fileExtension = ['jpeg', 'jpg', 'png', 'gif'];

  	if (input.files) {

    	[].forEach.call(input.files, function(file) {

      		var reader = new FileReader();

      		reader.onloadend = function(e) {

	      		if ($.inArray(file.name.split('.').pop().toLowerCase(), fileExtension) == -1) {
	      
	        		alert("Only formats are allowed : "+fileExtension.join(', '));
	      
	      		} else if (addedImages < maxImages && e.total < 10485760) {
	        
		          	var img = '<img src="'+e.target.result+'" class="image-preview">'
	          		$(append_div).append(img);

	          		addedImages++;
	        	}
	      	}

      		reader.readAsDataURL(file);
    	});
  	}
}

/** Delete answer function */
function delete_answer(id) {

	if(id > 0) {

		$("#deleteAnswerId").val(id);
		$('#deleteAnswerModal').modal('show');
	}
}

/** Vote Answer function */
function vote_answer(answer_id) {

	var value = parseInt($("#answer_"+answer_id+" .votes-number").val().replace(' votes', ''));

	$.getJSON('/answers/vote', { 'id': answer_id }, function(data) {

		if(data > 0) {

			var new_value = value + data;
  		$("#answer_"+answer_id+" .votes-number").val(new_value+' votes');
  		$("#answer_"+answer_id+" .click-unvote").removeClass('active');
  		$("#answer_"+answer_id+" .click-vote").addClass('active');
		}
	});
}

/** Unvote Answer function */
function unvote_answer(answer_id) {

	var value = parseInt($("#answer_"+answer_id+" .votes-number").val().replace(' votes', ''));

	$.getJSON('/answers/unvote', { 'id': answer_id }, function(data) {

		if(data > 0) {

			var new_value = value - data;
  		$("#answer_"+answer_id+" .votes-number").val(new_value+' votes');
  		$("#answer_"+answer_id+" .click-vote").removeClass('active');
  		$("#answer_"+answer_id+" .click-unvote").addClass('active');
		}
	});
}

/** Edit answer function */
function edit_answer(id) {

	if(id > 0) {

		$("#editAnswerModal .ql-snow").remove();
		$("#editAnswerForm .card-body").append('<div id="editor_answer"></div>');

		$("#edit_answer_id").val(id);
		$('#editAnswerModal').modal('show');

		$.getJSON('/answers/get', { 'id': id }, function(data) {
			
			var quill_answer = new Quill('#editor_answer', {
	      		modules: {
	        		toolbar: [
	          			[{ header: [3, 4, 5, 6, false], }],
	          			[{ 'list': 'ordered'}, { 'list': 'bullet' }],
	          			['bold', 'italic', 'underline', 'code-block', 'link', 'image', 'video'],
	        		],
	        		imageResize: {
	          			displaySize: true
	        		},
	        		videoResize: {
	        		},
	        		imageUpload: {
            			url: "/answers/upload-image",
            			method: "POST", 
            			headers: {},
            			callbackOK: (serverResponse, next) => {
              				next(serverResponse);   
            			},
            			callbackKO: (serverError) => {
              				console.log(serverError);
            			}
        			},
		        	mention: {
			          	allowedChars: /^[0-9A-Z_a-z]*$/,
			          	mentionDenotationChars: ["@"],
			          	source: function (searchTerm, renderList, mentionChar) {
			            	let values = [ { id: 1, value: searchTerm }, ];

				            if (searchTerm.length === 0) {
				              	renderList(values, searchTerm);
				            } else {
				              	const matches = [];
				              	for (i = 0; i < values.length; i++)
				                	if (~values[i].value.toLowerCase().indexOf(searchTerm.toLowerCase())) matches.push(values[i]);
				              	renderList(matches, searchTerm);
				            }
				        }
			        }
	      		},
	      		placeholder: 'Your answer',
	      		theme: 'snow'
	    	});

	    	quill_answer.root.innerHTML = data.answer;

	    	quill_answer.clipboard.addMatcher(Node.TEXT_NODE, function(node, delta) {

			  	var regex = /https?:\/\/[^\s]+/g;
			  	if(typeof(node.data) !== 'string') return;
			  	var matches = node.data.match(regex);

			  	if(matches && matches.length > 0) {
			    	var ops = [];
			    	var str = node.data;
			    	matches.forEach(function(match) {
			      		var split = str.split(match);
			      		var beforeLink = split.shift();
			      		ops.push({ insert: beforeLink });
			      		ops.push({ insert: match, attributes: { link: match } });
			      		str = split.join(match);
			    	});
			    	ops.push({ insert: str });
			    	delta.ops = ops;
			  	}

			  	return delta;
			});

			var form = document.querySelector('#editAnswerForm');

      		form.onsubmit = function() {

        		var answer = document.querySelector('input[name=new_answer]');
        		answer.value = quill_answer.root.innerHTML;
      		};
		});
	}
}

/** Report answer function */
function report_answer(id) {

	if(id > 0) {

		$("#report_answer_message").val('');
		$("#report_answer_id").val(id);
		$('#reportAnswerModal').modal('show');
		$('#report_url_back').val(window.location.href);
	}
}

/** Report question function */
function report_question(id) {

	if(id > 0) {

		$("#report_question_message").val('');
		$("#report_question_id").val(id);
		$('#reportQuestionModal').modal('show');
		$('#report_url_back').val(window.location.href);
	}
}