<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\TagBundle\Entity\Tag,
    Platformd\TagBundle\Entity\Tagging,
    Platformd\TagBundle\Model\TagManager,
    Platformd\SpoutletBundle\Form\Type\TagUploadType
;

use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response,
    Symfony\Component\HttpFoundation\File\File
;

class TagAdminController extends Controller
{
    public function indexAction(Request $request)
    {
        $this->addTagsBreadcrumb();
        $manager = $this->getTagManager();

        $tags = $manager->getAllTagsSortByAlphaWithCount();

        $form = $this->createForm(new TagUploadType());

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);
            $data = $form->getData();
            $attachment = $data['attachment'];

            if ($attachment && $form->isValid()) {
                $tagList = $this->getTagListFromCsv($attachment);
                $manager->loadOrCreateTags($tagList);

                $this->setFlash('success', $this->trans('tags.forms.upload_success'));
                return $this->redirect($this->generateUrl('admin_tags_index'));
            }

            $this->setFlash('error', $this->trans('tags.forms.upload_no_file_selected'));
            return $this->redirect($this->generateUrl('admin_tags_index'));
        }

        //[{value: 1, text: "text1"}, {value: 2, text: "text2"}, ...]
        $options = '[{value: "STATUS_ACTIVE", text: "' . Tag::STATUS_ACTIVE . '"}, {value: "STATUS_INAPPROPRIATE", text: "' . Tag::STATUS_INAPPROPRIATE .'"}]';

        return $this->render('SpoutletBundle:TagsAdmin:index.html.twig', array(
            'tags'      => $tags,
            'options'   => $options,
            'form'      => $form->createView(),
        ));
    }

    public function editAction(Request $request)
    {
        $response = new Response();
        $response->headers->set('Content-type', 'text/json; charset=utf-8');

        if (!$request->get('pk') || !$request->get('name') || !$request->get('value')) {
            $response->setContent(json_encode(array("success" => false, "message" => 'The request is missing some required information')));
            return $response;
        }

        $manager  = $this->getTagManager();
        $id       = $request->get('pk');
        $name     = $request->get('name');
        $value    = $request->get('value');
        $tag      = $manager->findTag($id);

        if(!$tag) {
            $response->setContent(json_encode(array("success" => false, "message" => 'Tag not found.')));
            return $response;
        }

        switch ($name) {
            case 'name':
                $tag->setName($value);
                break;

            case 'status':
                $status = $value == 'STATUS_ACTIVE' ? Tag::STATUS_ACTIVE : Tag::STATUS_INAPPROPRIATE;
                $tag->setStatus($status);
                break;

            default:
                # code...
                break;
        }

        $manager->saveTag($tag);

        $response->setContent(json_encode(array("success" => true, "message" => 'Tag updated successfully.')));
        return $response;
    }

    private function getTagListFromCsv(File $file)
    {
        $openFile   = $file->openFile();
        $tags       = array();

        while (!$openFile->eof()) {
            $csvRow = $openFile->fgetcsv();

            if (!$csvRow || empty($csvRow) || trim($csvRow[0]) == "") {
                continue;
            }

            $tags[] = $csvRow[0];
        }

        return $tags;
    }

    private function getTagManager()
    {
        return $this->get('platformd.tags.model.tag_manager');
    }

    private function addTagsBreadcrumb()
    {
        $this->getBreadcrumbs()->addChild('Tags', array());

        return $this->getBreadcrumbs();
    }
}
