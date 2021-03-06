<?php
class YouTubeResolverClass
{
    public $data,$status,$video_id,$stream_data;
    protected $prodata,$rawdata,$streams,$stream,$ch,$rcount,$count,$vurl_exp,$headers,$value;
    public function get_data($video_id)
    {
        if(empty($video_id) || strlen($video_id)!=11)//Check if $video_id is set and it's length is 11 characters
        {
            $this->status[]=__METHOD__." : Video ID not set properly";
            return false;
        }
        else {$this->status[]=__METHOD__." : Video ID successfully acquired.";}
        $this->data['video_id']=$video_id;
        $this->ch=curl_init('http://www.youtube.com/get_video_info?video_id='.$video_id);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);//To get the raw data in a string, we must use this
        $this->rawdata=curl_exec($this->ch);//Save the raw data in $rawdata
        curl_close($this->ch);
        if(empty($this->rawdata))//Checking if data was successfully obtained
        {
            $this->status[]=__METHOD__." : Problem fechting video data from YouTube";
            return false;
        }
        else
        {
            $this->status[]=__METHOD__." :Successfully fetched video data from YouTube";
        }
	//Process the raw data and create an array ($prodata) from it. $prodata stands for "processed data"
        parse_str($this->rawdata,$this->prodata);
        if(!isset($this->prodata['url_encoded_fmt_stream_map']))//Checking if video is protected.
        {
            $this->status[]=__METHOD__." : This video contains content from SME. It is restricted from playback on certain sites. This video can not be downloaded";
            return false;
            break;
        }
        $this->data['title']=$this->prodata['title'];
        $this->data['views']=$this->prodata['view_count'];
        $this->data['duration']=$this->prodata['length_seconds'];
        $this->data['language']=$this->prodata['hl'];
        $this->data['author']=$this->prodata['author'];
        $this->data['thumbnail_url']=$this->prodata['thumbnail_url'];
        $this->streams = explode(',',$this->prodata['url_encoded_fmt_stream_map']);//Split the stream map into streams.
        $this->count=$this->data['numstreams']=count($this->streams);
        $this->rcount=0;
        while ($this->rcount<$this->count)
        {
            
	//Select a stream "$stream" from the array "$streams"
	parse_str($this->streams[$this->rcount],$this->stream);
        // Create the proper URL
	$this->stream_data[$this->rcount]['url']=$this->stream['url'].'&signature='.$this->stream['sig'];
        $this->stream_data[$this->rcount]['is3D']=$this->stream_data[$this->rcount]['isLive']=$this->stream_data[$this->rcount]['isHD']=false;//Set default value as false
            switch($this->stream['itag'])//To extract data from itag value (Based on http://en.wikipedia.org/wiki/YouTube#Quality_and_codecs )
            {
                case '5' : $this->stream_data[$this->rcount]['type']="FLV (.flv) Video";
                $this->stream_data[$this->rcount]['fmt']="flv";
                $this->stream_data[$this->rcount]['vres']="240";
                $this->stream_data[$this->rcount]['vcod']="H.263 (Sorenson)";
                $this->stream_data[$this->rcount]['vbit']="0.25";
                $this->stream_data[$this->rcount]['acod']="MP3";
                $this->stream_data[$this->rcount]['abit']="64";
                $this->stream_data[$this->rcount]['rawtype']="video/x-flv";
                continue;
                case '6' : $this->stream_data[$this->rcount]['type']="FLV (.flv) Video";
                $this->stream_data[$this->rcount]['fmt']="flv";
                $this->stream_data[$this->rcount]['vres']="270";
                $this->stream_data[$this->rcount]['vcod']="H.263 (Sorenson)";
                $this->stream_data[$this->rcount]['vbit']="0.8";
                $this->stream_data[$this->rcount]['acod']="MP3";
                $this->stream_data[$this->rcount]['abit']="64";
                $this->stream_data[$this->rcount]['rawtype']="video/x-flv";
                continue;
                case '13' : $this->stream_data[$this->rcount]['type']="3GP (.3gp) Video";
                $this->stream_data[$this->rcount]['fmt']="3gp";
                $this->stream_data[$this->rcount]['vres']="N/A";
                $this->stream_data[$this->rcount]['vcod']="MPEG-4 Visual";
                $this->stream_data[$this->rcount]['vbit']="0.5";
                $this->stream_data[$this->rcount]['acod']="AAC";
                $this->stream_data[$this->rcount]['abit']="N/A";
                $this->stream_data[$this->rcount]['rawtype']="video/3gpp";
                continue;
                case '17' : $this->stream_data[$this->rcount]['type']="3GP (.3gp) Video";
                $this->stream_data[$this->rcount]['fmt']="3gp";
                $this->stream_data[$this->rcount]['vres']="144";
                $this->stream_data[$this->rcount]['vcod']="MPEG-4 Visual";
                $this->stream_data[$this->rcount]['vbit']="0.05";
                $this->stream_data[$this->rcount]['acod']="AAC";
                $this->stream_data[$this->rcount]['abit']="24";
                $this->stream_data[$this->rcount]['rawtype']="video/3gpp";
                continue;
                case '18' : $this->stream_data[$this->rcount]['type']="MP4 (.mp4) Video";
                $this->stream_data[$this->rcount]['fmt']="mp4";
                $this->stream_data[$this->rcount]['vres']="360";
                $this->stream_data[$this->rcount]['vcod']="H.264";
                $this->stream_data[$this->rcount]['vbit']="0.5";
                $this->stream_data[$this->rcount]['acod']="AAC";
                $this->stream_data[$this->rcount]['abit']="96";
                $this->stream_data[$this->rcount]['rawtype']="video/mp4";
                continue;
                case '22' : $this->stream_data[$this->rcount]['type']="MP4 (.mp4) Video";
                $this->stream_data[$this->rcount]['fmt']="mp4";
                $this->stream_data[$this->rcount]['vres']="720";
                $this->stream_data[$this->rcount]['vcod']="H.264";
                $this->stream_data[$this->rcount]['vbit']="2.4";
                $this->stream_data[$this->rcount]['acod']="AAC";
                $this->stream_data[$this->rcount]['abit']="192";
                $this->stream_data[$this->rcount]['rawtype']="video/mp4";
                $this->stream_data[$this->rcount]['isHD']=true;
                continue;
                case '34' : $this->stream_data[$this->rcount]['type']="FLV (.flv) Video";
                $this->stream_data[$this->rcount]['fmt']="flv";
                $this->stream_data[$this->rcount]['vres']="360";
                $this->stream_data[$this->rcount]['vcod']="H.264";
                $this->stream_data[$this->rcount]['vbit']="0.5";
                $this->stream_data[$this->rcount]['acod']="AAC";
                $this->stream_data[$this->rcount]['abit']="128";
                $this->stream_data[$this->rcount]['rawtype']="video/x-flv";
                continue;

                case '35' : $this->stream_data[$this->rcount]['type']="FLV (.flv) Video";
                $this->stream_data[$this->rcount]['fmt']="flv";
                $this->stream_data[$this->rcount]['vres']="480";
                $this->stream_data[$this->rcount]['vcod']="H.264";
                $this->stream_data[$this->rcount]['vbit']="1";
                $this->stream_data[$this->rcount]['acod']="AAC";
                $this->stream_data[$this->rcount]['abit']="128";
                $this->stream_data[$this->rcount]['rawtype']="video/x-flv";
                continue;
                case '36' : $this->stream_data[$this->rcount]['type']="3GP (.3gp) Video";
                $this->stream_data[$this->rcount]['fmt']="3gp";
                $this->stream_data[$this->rcount]['vres']="240";
                $this->stream_data[$this->rcount]['vcod']="MPEG-4 Visual";
                $this->stream_data[$this->rcount]['vbit']="0.17";
                $this->stream_data[$this->rcount]['acod']="AAC";
                $this->stream_data[$this->rcount]['abit']="38";
                $this->stream_data[$this->rcount]['rawtype']="video/3gpp";
                continue;
                case '37' : $this->stream_data[$this->rcount]['type']="MP4 (.mp4) Video";
                $this->stream_data[$this->rcount]['fmt']="mp4";
                $this->stream_data[$this->rcount]['vres']="1080";
                $this->stream_data[$this->rcount]['vcod']="H.264";
                $this->stream_data[$this->rcount]['vbit']="3.7";
                $this->stream_data[$this->rcount]['acod']="AAC";
                $this->stream_data[$this->rcount]['abit']="192";
                $this->stream_data[$this->rcount]['rawtype']="video/mp4";
                $this->stream_data[$this->rcount]['isHD']=true;
                continue;
                case '38' : $this->stream_data[$this->rcount]['type']="MP4 (.mp4) Video";
                $this->stream_data[$this->rcount]['fmt']="mp4";
                $this->stream_data[$this->rcount]['vres']="3072";
                $this->stream_data[$this->rcount]['vcod']="H.264";
                $this->stream_data[$this->rcount]['vbit']="4.5";
                $this->stream_data[$this->rcount]['acod']="AAC";
                $this->stream_data[$this->rcount]['abit']="192";
                $this->stream_data[$this->rcount]['rawtype']="video/mp4";
                $this->stream_data[$this->rcount]['isHD']=true;
                continue;
                case '43' : $this->stream_data[$this->rcount]['type']="WebM (.webm) Video";
                $this->stream_data[$this->rcount]['fmt']="webm";
                $this->stream_data[$this->rcount]['vres']="360";
                $this->stream_data[$this->rcount]['vcod']="VP8";
                $this->stream_data[$this->rcount]['vbit']="0.5";
                $this->stream_data[$this->rcount]['acod']="Vorbis";
                $this->stream_data[$this->rcount]['abit']="128";
                $this->stream_data[$this->rcount]['rawtype']="video/webm";
                continue;
                case '44' : $this->stream_data[$this->rcount]['type']="WebM (.webm) Video";
                $this->stream_data[$this->rcount]['fmt']="webm";
                $this->stream_data[$this->rcount]['vres']="480";
                $this->stream_data[$this->rcount]['vcod']="VP8";
                $this->stream_data[$this->rcount]['vbit']="1";
                $this->stream_data[$this->rcount]['acod']="Vorbis";
                $this->stream_data[$this->rcount]['abit']="128";
                $this->stream_data[$this->rcount]['rawtype']="video/webm";
                continue;
                case '45' : $this->stream_data[$this->rcount]['type']="WebM (.webm) Video";
                $this->stream_data[$this->rcount]['fmt']="webm";
                $this->stream_data[$this->rcount]['vres']="720";
                $this->stream_data[$this->rcount]['vcod']="VP8";
                $this->stream_data[$this->rcount]['vbit']="2";
                $this->stream_data[$this->rcount]['acod']="Vorbis";
                $this->stream_data[$this->rcount]['abit']="192";
                $this->stream_data[$this->rcount]['rawtype']="video/webm";
                $this->stream_data[$this->rcount]['isHD']=true;
                continue;
                case '46' : $this->stream_data[$this->rcount]['type']="WebM (.webm) Video";
                $this->stream_data[$this->rcount]['fmt']="webm";
                $this->stream_data[$this->rcount]['vres']="1080";
                $this->stream_data[$this->rcount]['vcod']="VP8";
                $this->stream_data[$this->rcount]['vbit']="N/A";
                $this->stream_data[$this->rcount]['acod']="Vorbis";
                $this->stream_data[$this->rcount]['abit']="192";
                $this->stream_data[$this->rcount]['rawtype']="video/webm";
                $this->stream_data[$this->rcount]['isHD']=true;
                continue;
                case '82' : $this->stream_data[$this->rcount]['type']="MPEG-4 (.mp4) Video";
                $this->stream_data[$this->rcount]['fmt']="mp4";
                $this->stream_data[$this->rcount]['vres']="360";
                $this->stream_data[$this->rcount]['vcod']="H.264";
                $this->stream_data[$this->rcount]['vbit']="0.5";
                $this->stream_data[$this->rcount]['acod']="AAC";
                $this->stream_data[$this->rcount]['abit']="96";
                $this->stream_data[$this->rcount]['rawtype']="video/mp4";
                $this->stream_data[$this->rcount]['isHD']=true;
                continue;
                case '83' : $this->stream_data[$this->rcount]['type']="MPEG-4 (.mp4) Video";
                $this->stream_data[$this->rcount]['fmt']="mp4";
                $this->stream_data[$this->rcount]['vres']="240";
                $this->stream_data[$this->rcount]['vcod']="H.264";
                $this->stream_data[$this->rcount]['vbit']="0.5";
                $this->stream_data[$this->rcount]['acod']="AAC";
                $this->stream_data[$this->rcount]['abit']="96";
                $this->stream_data[$this->rcount]['rawtype']="video/mp4";
                $this->stream_data[$this->rcount]['isHD']=true;
                continue;
                case '84' : $this->stream_data[$this->rcount]['type']="MPEG-4 (.mp4) Video";
                $this->stream_data[$this->rcount]['fmt']="mp4";
                $this->stream_data[$this->rcount]['vres']="720";
                $this->stream_data[$this->rcount]['vcod']="H.264";
                $this->stream_data[$this->rcount]['vbit']="2.4";
                $this->stream_data[$this->rcount]['acod']="AAC";
                $this->stream_data[$this->rcount]['abit']="152";
                $this->stream_data[$this->rcount]['rawtype']="video/mp4";
                $this->stream_data[$this->rcount]['isHD']=true;
                $this->stream_data[$this->rcount]['isHD']=true;
                continue;
                case '85' : $this->stream_data[$this->rcount]['type']="MPEG-4 (.mp4) Video";
                $this->stream_data[$this->rcount]['fmt']="mp4";
                $this->stream_data[$this->rcount]['vres']="520";
                $this->stream_data[$this->rcount]['vcod']="H.264";
                $this->stream_data[$this->rcount]['vbit']="2.4";
                $this->stream_data[$this->rcount]['acod']="AAC";
                $this->stream_data[$this->rcount]['abit']="152";
                $this->stream_data[$this->rcount]['rawtype']="video/mp4";
                $this->stream_data[$this->rcount]['isHD']=true;
                continue;
                case '100' : $this->stream_data[$this->rcount]['type']="WebM (.webm) Video";
                $this->stream_data[$this->rcount]['fmt']="webm";
                $this->stream_data[$this->rcount]['vres']="360";
                $this->stream_data[$this->rcount]['vcod']="VP8";
                $this->stream_data[$this->rcount]['vbit']="N/A";
                $this->stream_data[$this->rcount]['acod']="Vorbis";
                $this->stream_data[$this->rcount]['abit']="128";
                $this->stream_data[$this->rcount]['rawtype']="video/webm";
                $this->stream_data[$this->rcount]['isHD']=true;
                continue;
                case '101' : $this->stream_data[$this->rcount]['type']="WebM (.webm) Video";
                $this->stream_data[$this->rcount]['fmt']="webm";
                $this->stream_data[$this->rcount]['vres']="360";
                $this->stream_data[$this->rcount]['vcod']="VP8";
                $this->stream_data[$this->rcount]['vbit']="N/A";
                $this->stream_data[$this->rcount]['acod']="Vorbis";
                $this->stream_data[$this->rcount]['abit']="192";
                $this->stream_data[$this->rcount]['rawtype']="video/webm";
                $this->stream_data[$this->rcount]['isHD']=true;
                continue;
                case '102' : $this->stream_data[$this->rcount]['type']="WebM (.webm) Video";
                $this->stream_data[$this->rcount]['fmt']="webm";
                $this->stream_data[$this->rcount]['vres']="720";
                $this->stream_data[$this->rcount]['vcod']="VP8";
                $this->stream_data[$this->rcount]['vbit']="N/A";
                $this->stream_data[$this->rcount]['acod']="Vorbis";
                $this->stream_data[$this->rcount]['abit']="192";
                $this->stream_data[$this->rcount]['rawtype']="video/webm";
                $this->stream_data[$this->rcount]['isHD']=true;
                $this->stream_data[$this->rcount]['isHD']=true;
                continue;
                case '120' : $this->stream_data[$this->rcount]['type']="FLV (.flv) Video";
                $this->stream_data[$this->rcount]['fmt']="flv";
                $this->stream_data[$this->rcount]['vres']="720";
                $this->stream_data[$this->rcount]['vcod']="AVC";
                $this->stream_data[$this->rcount]['vbit']="2";
                $this->stream_data[$this->rcount]['acod']="AAC";
                $this->stream_data[$this->rcount]['abit']="128";
                $this->stream_data[$this->rcount]['rawtype']="video/x-flv";
                $this->stream_data[$this->rcount]['isHD']=true;
                $this->stream_data[$this->rcount]['isLive']=true;
                continue;
                default :$this->status[]=__METHOD__." : Problem in getting data from itag value ";// If this occurs to you, Send me an email / PM with the video id
                return false;
                continue;
            }
            $this->rcount++;
        }
        $this->status[]=__METHOD__." : Successfully created stream_data.";
        return true;
    }
    public function get_vid($vurl)
    {
        $this->vurl_exp=explode('?v=',$vurl);
        if($this->vurl_exp===$vurl)
        {
            $this->status[]=__METHOD__." : Improper URL was given";
            return false;
        }
        $this->data['video_id']=$this->vurl_exp[1];
        $this->status[]=__METHOD__." : Video ID extracted successfully.";
        return $this->data['video_id'];
    }
    public function stream_video($key)
    {
       if($this->headers=get_headers($this->stream_data[$key]['url']))//Get headers from YouTube
        $this->status[]=__METHOD__." : Successfully got headers from YouTube.";
        foreach ($this->headers as $this->value)//For sending each and every header
        {
            if(header($this->value))
           $this->status[]=__METHOD__." : Successfully sent header.";  
        }
        $this->ch=curl_init($this->stream_data[$key]['url']);//Initialize cURL
        if(curl_exec($this->ch))
        $this->status[]=__METHOD__." : Successful stream output.";
        curl_close($this->ch);
    }
}
?>
