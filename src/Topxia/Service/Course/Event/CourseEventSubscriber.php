<?php
namespace Topxia\Service\Course\Event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Topxia\Common\StringToolkit;
use Topxia\Service\Common\ServiceEvent;
use Topxia\Service\Common\ServiceKernel;

class CourseEventSubscriber implements EventSubscriberInterface
{

    public static function getSubscribedEvents()
    {
        return array(
            'course.lesson_start' => 'onLessonStart',
            'course.lesson_finish' => 'onLessonFinish',
            'course.join' => 'onCourseJoin',
            'course.favorite' => 'onCourseFavorite',
            'course.note.create' => 'onCourseNoteCreate',
            'course.note.update' => 'onCourseNoteUpdate',
            'course.note.delete' => 'onCourseNoteDelete',
            'course.note.liked' => 'onCourseNoteLike',
            'course.note.cancelLike' => 'onCourseNoteCancelLike',
        );
    }

    public function onLessonStart(ServiceEvent $event)
    {
        $lesson = $event->getSubject();
        $course = $event->getArgument('course');
        $this->getStatusService()->publishStatus(array(
            'type' => 'start_learn_lesson',
            'courseId' => $course['id'],
            'objectType' => 'lesson',
            'objectId' => $lesson['id'],
            'private' => $course['status'] == 'published' ? 0 : 1,
            'properties' => array(
                'course' => $this->simplifyCousrse($course),
                'lesson' => $this->simplifyLesson($lesson),
            ),
        ));
    }

    public function onLessonFinish(ServiceEvent $event)
    {
        $lesson = $event->getSubject();
        $course = $event->getArgument('course');
        $this->getStatusService()->publishStatus(array(
            'type' => 'learned_lesson',
            'courseId' => $course['id'],
            'objectType' => 'lesson',
            'objectId' => $lesson['id'],
            'private' => $course['status'] == 'published' ? 0 : 1,
            'properties' => array(
                'course' => $this->simplifyCousrse($course),
                'lesson' => $this->simplifyLesson($lesson),
            ),
        ));
    }

    public function onCourseFavorite(ServiceEvent $event)
    {
        $course = $event->getSubject();
        $this->getStatusService()->publishStatus(array(
            'type' => 'favorite_course',
            'courseId' => $course['id'],
            'objectType' => 'course',
            'objectId' => $course['id'],
            'private' => $course['status'] == 'published' ? 0 : 1,
            'properties' => array(
                'course' => $this->simplifyCousrse($course),
            ),
        ));
    }

    public function onCourseJoin(ServiceEvent $event)
    {
        $course = $event->getSubject();
        $userId = $event->getArgument('userId');
        $this->getStatusService()->publishStatus(array(
            'type' => 'become_student',
            'courseId' => $course['id'],
            'objectType' => 'course',
            'objectId' => $course['id'],
            'private' => $course['status'] == 'published' ? 0 : 1,
            'userId' => $userId,
            'properties' => array(
                'course' => $this->simplifyCousrse($course),
            ),
        ));
    }

    public function onCourseNoteCreate(ServiceEvent $event)
    {
        $note = $event->getSubject();
        $classroom = $this->getClassroomService()->findClassroomByCourseId($note['courseId']);
        $course = $this->getCourseService()->getCourse($note['courseId']);
        if ($classroom && $note['status']) {
            $this->getClassroomService()->waveClassroom($classroom['classroomId'], 'noteNum', +1);
        }

        if ($course && $note['status']) {
            $this->getCourseService()->waveCourse($note['courseId'], 'noteNum', +1);
        }
    }

    public function onCourseNoteUpdate(ServiceEvent $event)
    {
        $note = $event->getSubject();
        $preStatus = $event->getArgument('preStatus');
        $classroom = $this->getClassroomService()->findClassroomByCourseId($note['courseId']);
        $course = $this->getCourseService()->getCourse($note['courseId']);
        if ($classroom && $note['status'] && !$preStatus) {
            $this->getClassroomService()->waveClassroom($classroom['classroomId'], 'noteNum', +1);
        }

        if ($classroom && !$note['status'] && $preStatus) {
            $this->getClassroomService()->waveClassroom($classroom['classroomId'], 'noteNum', -1);
        }

        if ($course && $note['status'] && !$preStatus) {
            $this->getCourseService()->waveCourse($note['courseId'], 'noteNum', +1);
        }

        if ($course && !$note['status'] && $preStatus) {
            $this->getCourseService()->waveCourse($note['courseId'], 'noteNum', -1);
        }

    }

    public function onCourseNoteDelete(ServiceEvent $event)
    {
        $note = $event->getSubject();
        $classroom = $this->getClassroomService()->findClassroomByCourseId($note['courseId']);
        if ($classroom) {
            $this->getClassroomService()->waveClassroom($classroom['classroomId'], 'noteNum', -1);
        }

        $course = $this->getCourseService()->getCourse($note['courseId']);
        if ($course) {
            $this->getCourseService()->waveCourse($note['courseId'], 'noteNum', -1);
        }
    }

    public function onCourseNoteLike(ServiceEvent $event)
    {
        $note = $event->getSubject();
        $this->getNoteService()->count($note['id'], 'likeNum', +1);
    }

    public function onCourseNoteCancelLike(ServiceEvent $event)
    {
        $note = $event->getSubject();
        $this->getNoteService()->count($note['id'], 'likeNum', -1);
    }

    protected function simplifyCousrse($course)
    {
        return array(
            'id' => $course['id'],
            'title' => $course['title'],
            'picture' => $course['middlePicture'],
            'type' => $course['type'],
            'rating' => $course['rating'],
            'about' => StringToolkit::plain($course['about'], 100),
            'price' => $course['price'],
        );
    }

    protected function simplifyLesson($lesson)
    {
        return array(
            'id' => $lesson['id'],
            'number' => $lesson['number'],
            'type' => $lesson['type'],
            'title' => $lesson['title'],
            'summary' => StringToolkit::plain($lesson['summary'], 100),
        );
    }

    protected function getStatusService()
    {
        return ServiceKernel::instance()->createService('User.StatusService');
    }

    protected function getNoteService()
    {
        return ServiceKernel::instance()->createService('Course.NoteService');
    }

    protected function getCourseService()
    {
        return ServiceKernel::instance()->createService('Course.CourseService');
    }

    protected function getClassroomService()
    {
        return ServiceKernel::instance()->createService('Classroom:Classroom.ClassroomService');
    }

}
