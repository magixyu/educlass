<?php
namespace Classroom\ClassroomBundle\Controller\Course;

use Symfony\Component\HttpFoundation\Request;
use Topxia\WebBundle\Controller\BaseController;
use Topxia\Common\ArrayToolkit;

class NoteController extends BaseController
{
    public function listAction(Request $request, $classroomId)
    {
        $classroom = $this->getClassroomService()->getClassroom($classroomId);

        $classroomCourses = $this->getClassroomService()->findActiveCoursesByClassroomId($classroomId);
        $courseIds = ArrayToolkit::column($classroomCourses, 'id');
        $courses = $this->getCourseService()->findCoursesByIds($courseIds);

        $user = $this->getCurrentUser();

        $member = $user ? $this->getClassroomService()->getClassroomMember($classroom['id'], $user['id']) : null;

        if ($classroom['private'] && (!$member || ($member && $member['locked']))) {
            return $this->createMessageResponse('error', '该班级是封闭班级,您无权查看');
        }

        $layout = 'ClassroomBundle:Classroom:layout.html.twig';
        if ($member && !$member['locked']) {
            $layout = 'ClassroomBundle:Classroom:join-layout.html.twig';
        }
        if(!$classroom){
            $classroomDescription = array();
        }
        else{
        $classroomDescription = $classroom['about'];
        $classroomDescription = strip_tags($classroomDescription,'');
        $classroomDescription = preg_replace("/ /","",$classroomDescription);
    }
        return $this->render('ClassroomBundle:Classroom\Course:notes-list.html.twig', array(
            'layout' => $layout,
            'filters' => $this->getNoteSearchFilters($request),
            'canLook' => $this->getClassroomService()->canLookClassroom($classroom['id']),
            'classroom' => $classroom,
            'courseIds' => $courseIds,
            'courses' => $courses,
            'member' => $member,
            'classroomDescription' => $classroomDescription
        ));
    }

    private function getNoteSearchFilters($request)
    {
        $filters = array();

        $filters['courseId'] = $request->query->get('courseId', '');
        $filters['sort'] = $request->query->get('sort');

        if (!in_array($filters['sort'], array('latest', 'likeNum'))) {
            $filters['sort'] = 'latest';
        }

        return $filters;
    }

    private function getClassroomService()
    {
        return $this->getServiceKernel()->createService('Classroom:Classroom.ClassroomService');
    }

    private function getCourseService()
    {
        return $this->getServiceKernel()->createService('Course.CourseService');
    }
}
