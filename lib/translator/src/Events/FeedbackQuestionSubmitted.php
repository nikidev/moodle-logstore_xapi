<?php namespace MXTranslator\Events;

class FeedbackQuestionSubmitted extends FeedbackSubmitted {
    /**
     * Reads data for an event.
     * @param [String => Mixed] $opts
     * @return [String => Mixed]
     * @override AttemtStarted
     */
    public function read(array $opts) {
        $translatorEvents = [];

        $feedback = parent::parseFeedback($opts);

        // Push question statements to $translatorEvents['events'].
        foreach ($feedback->questions as $questionId => $questionAttempt) {
            array_push(
                $translatorEvents,
                $this->questionStatement(
                    parent::read($opts)[0],
                    $questionAttempt
                )
            );
        }

        return $translatorEvents;
    }

    /**
     * Build a translator event for an individual question attempt.
     * @param [String => Mixed] $template
     * @param PHPObj $questionAttempt
     * @param PHPObj $question
     * @return [String => Mixed]
     */
    protected function questionStatement($template, $questionAttempt) {

        $translatorEvent = [
            'recipe' => 'attempt_question_completed',
            'question_attempt_ext' => $questionAttempt,
            'question_attempt_ext_key' => 'http://lrs.learninglocker.net/define/extensions/moodle_feedback_question_attempt',
            'question_ext' => $questionAttempt->question,
            'question_ext_key' => 'http://lrs.learninglocker.net/define/extensions/moodle_feedback_question',
            'question_name' => $questionAttempt->question->name ?: 'A Moodle feedback question',
            'question_description' => $questionAttempt->question->name ?: 'A Moodle feedback question',
            'question_url' => $questionAttempt->question->url,
            'attempt_score_scaled' => $questionAttempt->score->scaled,
            'attempt_score_raw' => $questionAttempt->score->raw, 
            'attempt_score_min' => $questionAttempt->score->min, 
            'attempt_score_max' => $questionAttempt->score->max,
            'attempt_response' => $questionAttempt->response,
            'attempt_success' => null,
            'attempt_completed' => true,
            'interaction_correct_responses' => null,
            'attempt_ext' => null // For questions the attempt extension is not used, so there's no need to pass that bulk of data
        ];

        switch ($questionAttempt->question->typ) {
            case 'multichoice':
                $translatorEvent['interaction_type'] = 'choice';
                $translatorEvent['interaction_choices'] = (object)[];
                foreach ($questionAttempt->options as $index => $option) {
                    $translatorEvent['interaction_choices']->$index = $option->description;
                }
                break;
            case 'multichoicerated':
                $translatorEvent['interaction_type'] = 'likert';
                $translatorEvent['interaction_scale'] = (object)[];
                foreach ($questionAttempt->options as $index => $option) {
                    $translatorEvent['interaction_scale']->$index = $option->description;
                }
                break;
            case 'textfield':
                $translatorEvent['interaction_type'] = 'fill-in';
                break;
            case 'textarea':
                $translatorEvent['interaction_type'] = 'long-fill-in';
                break;
            case 'numeric':
                $translatorEvent['interaction_type'] = 'numeric';
                break;
            case 'info':
                $translatorEvent['interaction_type'] = 'other';
                break;
            default:
                // Unsupported type. 
                break;
        }

        return array_merge($template, $translatorEvent);
    }

}