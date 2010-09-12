{*
 *  project.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Project summary template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}

 {include file='header.tpl'}

 {* Nav *}
 <div class="page_nav">
   {$resources->GetResource('summary')} | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=shortlog">{$resources->GetResource('shortlog')}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=log">{$resources->GetResource('log')}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=commit&h={$head->GetHash()}">{$resources->GetResource('commit')}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=commitdiff&h={$head->GetHash()}">{$resources->GetResource('commitdiff')}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=tree">{$resources->GetResource('tree')}</a>
   <br /><br />
 </div>

 {include file='title.tpl'}

 {* Project brief *}
 <table cellspacing="0">
   <tr><td>{$resources->GetResource('description')}</td><td>{$project->GetDescription()}</td></tr>
   <tr><td>{$resources->GetResource('owner')}</td><td>{$project->GetOwner()}</td></tr>
   <tr><td>{$resources->GetResource('last change')}</td><td>{$head->GetCommitterEpoch()|date_format:"%a, %d %b %Y %H:%M:%S %z"}</td></tr>
   {if $project->GetCloneUrl()}
     <tr><td>{$resources->GetResource('clone url')}</td><td>{$project->GetCloneUrl()}</td></tr>
   {/if}
   {if $project->GetPushUrl()}
     <tr><td>{$resources->GetResource('push url')}</td><td>{$project->GetPushUrl()}</td></tr>
   {/if}
 </table>

 {include file='title.tpl' target='shortlog'}
 
 <table cellspacing="0">
   {foreach from=$revlist item=rev}
     <tr class="{cycle name=revs values="light,dark"}">
     <td><em>{$rev->GetAge()|agestring}</em></td>
     <td><em>{$rev->GetAuthorName()}</em></td>
     <td>
       <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=commit&h={$rev->GetHash()}" class="list commitTip" {if strlen($rev->GetTitle()) > 50}title="{$rev->GetTitle()}"{/if}><strong>{$rev->GetTitle(50)}</strong></a>
       <span class="refs">
       {foreach item=revhead from=$rev->GetHeads()}
         <span class="head">
           <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=shortlog&h=refs/heads/{$revhead->GetName()}">{$revhead->GetName()}</a>
         </span>
       {/foreach}
       {foreach item=revtag from=$rev->GetTags()}
         <span class="tag">
           <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=tag&h={$revtag->GetName()}">{$revtag->GetName()}</a>
         </span>
       {/foreach}
       </span>
     </td>
     <td class="link"><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=commit&h={$rev->GetHash()}">{$resources->GetResource('commit')}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=commitdiff&h={$rev->GetHash()}">{$resources->GetResource('commitdiff')}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=tree&h={$rev->GetHash()}&hb={$rev->GetHash()}">{$resources->GetResource('tree')}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=snapshot&h={$rev->GetHash()}">{$resources->GetResource('snapshot')}</a></td>
     </tr>
   {/foreach}
   {if $hasmorerevs}
     <tr class="light">
       <td><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=shortlog">...</a></td>
     </tr>
   {/if}
 </table>
 {if $taglist}
   {* Tags *}
  
  {include file='title.tpl' target='tags'}
   
   <table cellspacing="0">
     {section name=tag max=17 loop=$taglist}
       <tr class="{cycle name=tags values="light,dark"}">
         {if $smarty.section.tag.index == 16}
           <td><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=tags">...</a></td>
         {else}
	   {assign var=object value=$taglist[tag]->GetObject()}
	   {assign var=objtype value=$taglist[tag]->GetType()}
           <td><em>{$object->GetAge()|agestring}</em></td>
           <td><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a={$objtype}&h={$object->GetHash()}" class="list"><strong>{$taglist[tag]->GetName()}</strong></a></td>
           <td>
	     {assign var=comment value=$taglist[tag]->GetComment()}
             {if count($comment) > 0}
               <a class="list" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=tag&h={$taglist[tag]->GetName()}">{$comment[0]}</a>
             {/if}
           </td>
           <td class="link">
             {if !$taglist[tag]->LightTag()}
   	       <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=tag&h={$taglist[tag]->GetName()}">{$resources->GetResource('tag')}</a> | 
             {/if}
             <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a={$objtype}&h={$object->GetHash()}">{$resources->GetResource($objtype)}</a>
	     {if $objtype == "commit"}
	      | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=shortlog&h=refs/tags/{$taglist[tag]->GetName()}">{$resources->GetResource('shortlog')}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=log&h=refs/tags/{$taglist[tag]->GetName()}">{$resources->GetResource('log')}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=snapshot&h={$object->GetHash()}">{$resources->GetResource('snapshot')}</a>{/if}
           </td>
         {/if}
       </tr>
     {/section}
   </table>
 {/if}
 {if $headlist}
   {* Heads *}

  {include file='title.tpl' target='heads'}

   <table cellspacing="0">
     {section name=head max=17 loop=$headlist}
       <tr class="{cycle name=heads values="light,dark"}">
         {if $smarty.section.head.index == 16}
           <td><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=heads">...</a></td>
         {else}
	   {assign var=headcommit value=$headlist[head]->GetCommit()}
           <td><em>{$headcommit->GetAge()|agestring}</em></td>
           <td><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=shortlog&h=refs/heads/{$headlist[head]->GetName()}" class="list"><strong>{$headlist[head]->GetName()}</strong></td>
           <td class="link"><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=shortlog&h=refs/heads/{$headlist[head]->GetName()}">{$resources->GetResource('shortlog')}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=log&h=refs/heads/{$headlist[head]->GetName()}">{$resources->GetResource('log')}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=tree&hb={$headcommit->GetHash()}">{$resources->GetResource('tree')}</a></td>
         {/if}
       </tr>
     {/section}
   </table>
 {/if}

 {include file='footer.tpl'}

