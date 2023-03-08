import axios from "axios"
import React, { useState } from "react"
import BaseUrl from "../Const/BaseUrl"

function CardUser({matricule, objs}){
    const [details, setDetails] = useState(null)
    const [id_user, setIdUser] = useState(null)
    let data = []
    let detail_user = null
    let detail = []


    const handleVoirDetail = (e) => {
        e.preventDefault()
        setIdUser(e.currentTarget.id)
        //let divsDetails = document.getElementsByClassName('details')

        axios({
            method:'get',
            url:BaseUrl.concat("recap-planning-user"),
            //url: "http://192.168.8.5:3000/src/json/recap-planning-dossier.json",
            responseType:'json',
            }).then(function(response) {
                setDetails(response.data.planning_list)         
              }) .catch(function(error) {
                setButtonSubmitDisabled(false)
                setLoadingUserBegin(false)
                alert("Une erreur s'est produite")
                if (error.response) {
                  // Request made and server responded
                  console.log(error.response.data);
                  console.log(error.response.status);
                  console.log(error.response.headers);
                } else if (error.request) {
                  // The request was made but no response was received
                  console.log("request", error.request);
                } else {
                  // Something happened in setting up the request that triggered an Error
                  console.log('Error', error.message);
                }
              })

    }
    if(details != null){
        //detail_user = []
        
        for(let matricule in details){
            if(parseInt(matricule) == id_user){
                
                for(let obj in details[matricule]["data"]){
                    detail.push( <tr>
                                    <td>{details[matricule]["data"][obj]["nom_dossier"]}</td>
                                    <td>{details[matricule]["data"][obj]["nom_etape"]}</td>
                                    <td>{details[matricule]["data"][obj]["nom_fichiers"]}</td>
                                </tr>
                    )
                        
                        
                }
            }
        }
            detail_user = 
                            <table className="table">
                                <thead>
                                    <tr>
                                        <th>Nom dossier</th>
                                        <th>Etapes</th>
                                        <th>Nom fichiers</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {detail}
                                </tbody>
                            </table>
                            
        //console.log(detail_user)
    }

    for(let i in objs){
        data.push(
            <tr key={"tr"+i+matricule+objs[i]["dossier"]}>
                <td style={{fontSize:"0.8em"}}>{objs[i]["dossier"]}</td>
                <td style={{fontSize:"0.8em"}}>{objs[i]["etape"]}</td>
                <td style={{fontSize:"0.8em"}}>{objs[i]["nb_file"]}</td>
            </tr>
        )
    }
        return (
            <div className="col-sm-12">
                <div className="card">
                    <div className="card-header">
                        <p className="text-danger">Matricule: {matricule}</p>
                    </div>
                    <div className="card-body">
                        <table className="table" key={matricule}>
                            <thead>
                                <tr>
                                    <th style={{fontSize:"0.8em"}}>Nom dossier</th>
                                    <th style={{fontSize:"0.8em"}} >Etapes</th>
                                    <th style={{fontSize:"0.8em"}} >Nombre de Fichiers</th>
                                </tr>
                            </thead>
                            <tbody>
                                {data}
                            </tbody>
                        </table>
                        <br/>
                        <a href="#" className="col-md-12 btn btn-primary" id={matricule} onClick={handleVoirDetail}>Voir la Detail</a>
                        <div className="details">
                            {detail_user}
                        </div>
                    </div>
                </div>
                
            <br/>
        </div>       
        )

}
export default CardUser